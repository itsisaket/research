<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;
use app\components\ApiAuthService;

class SiteController extends Controller
{
    /** ปรับค่ามาตรฐานได้ที่นี่ */
    private const SESSION_DURATION = 60 * 60 * 24 * 14; // 14 วัน
    private const CLOCK_SKEW       = 120;               // ยอม clock-skew 120s
    private const MAX_BODY_BYTES   = 1048576;           // 1MB

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        // หน้า public เข้าได้หมด
                        'actions' => ['index', 'login', 'error', 'about'],
                        'allow'   => true,
                    ],
                    [
                        'actions' => ['logout', 'my-profile'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    // NOTE: ถ้าต้องการให้หน้า JS เรียกได้แม้ยังไม่ login
                    // ให้ย้าย 'my-profile' ไปอยู่ rule ด้านบน
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'my-profile' => ['POST'],
                    'logout'     => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $isGuest = Yii::$app->user->isGuest;

        if ($isGuest) {
            $u = null;
        } else {
            $u = Yii::$app->user->identity; // อาจเป็น Account (AR) หรือ User (stateless)
        }

        // ถ้าต้องการแสดงชื่อหรืออีเมลในหน้า index
        $displayName = null;
        $displayEmail = null;

        if ($u) {
            // รองรับทั้งกรณีเป็น Account (DB) หรือ User (JWT)
            $displayName  = $u->uname ?? $u->name ?? 'ไม่ระบุชื่อ';
            $displayEmail = $u->email ?? '-';
        }

        return $this->render('index', [
            'isGuest' => $isGuest,
            'u' => $u,
            'displayName' => $displayName,
            'displayEmail' => $displayEmail,
        ]);
    }

    public function actionLogin()
    {
        // ถ้าล็อกอินอยู่แล้วก็กลับหน้าแรก
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        // ✅ อันเดิมของคุณ
        try {
            Yii::$app->sso->tryAutoLoginFromCookie();
            if (!Yii::$app->user->isGuest) {
                return $this->goHome();
            }
        } catch (\Throwable $e) {
            Yii::warning('SSO auto-login failed: ' . $e->getMessage(), 'sso');
        }

        return $this->render('login');
    }

    /**
     * ✅ แอ็กชันรับ sync จากหน้า login.js
     * รับ JSON: { "token": "...", "profile": {...} }
     * แล้ว login เข้า Yii + เก็บ/อัปเดตข้อมูลในตาราง user
     */
    public function actionMyProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // 1) รับ JSON
        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = Yii::$app->request->post();
        }

        $token   = $data['token']   ?? null;
        $profile = $data['profile'] ?? [];

        if (!$token) {
            return ['ok' => false, 'error' => 'no token'];
        }

        // 2) กรณีที่โปรไฟล์ที่ browser ส่งมามีไม่ครบ → ไปขอดึงจาก API อีกที
        //    (กันกรณีฝั่ง JS ส่งมาไม่ครบ หรือ API ฝั่งหน้าเว็บยังไม่เรียก /authen/profile)
        $personalId = $profile['personal_id'] ?? null;

        // ถ้ามี component apiAuth ให้ลองดึงตัวเต็มมาก่อน
        $fullProfile = null;
        try {
            /** @var ApiAuthService $apiAuth */
            $apiAuth = Yii::$app->apiAuth ?? null;
            if ($apiAuth instanceof ApiAuthService) {
                if ($personalId) {
                    // เรียกแบบ POST ที่คุณเขียนไว้
                    $fullProfile = $apiAuth->fetchProfileWithPost($token, $personalId);
                }
                if (!$fullProfile) {
                    // เรียกแบบ static ยิงตรง
                    $fullProfile = ApiAuthService::fetchProfileByToken($token);
                }
            } else {
                // ไม่มี component → ใช้แบบ static อย่างเดียว
                $fullProfile = ApiAuthService::fetchProfileByToken($token);
            }
        } catch (\Throwable $e) {
            // ถ้าดึงไม่สำเร็จให้ใช้โปรไฟล์ที่ client ส่งมา
            Yii::warning('Fetch profile from API failed: ' . $e->getMessage(), 'sso');
        }

        if (is_array($fullProfile) && !empty($fullProfile)) {
            $profile = $fullProfile;
        }

        // ถึงตรงนี้ $profile ต้องมีอย่างน้อย personal_id
        $pid = $profile['personal_id'] ?? null;
        if (!$pid) {
            return ['ok' => false, 'error' => 'profile has no personal_id'];
        }

        // 3) หา user ในฐานข้อมูลตาม personal_id
        /** @var User $user */
        $user = User::findOne(['personal_id' => $pid]);

        // 4) ถ้าไม่เจอ → สร้างใหม่
        if ($user === null) {
            $user = new User();
            $user->personal_id = $pid;
            // ตั้งค่าเริ่มต้นที่จำเป็น
            $user->username = $pid; // หรือจะตั้งเป็น email ก็ได้
            $user->status   = 10;
            if ($user->hasAttribute('auth_key')) {
                $user->auth_key = Yii::$app->security->generateRandomString();
            }
            if ($user->hasAttribute('created_at')) {
                $user->created_at = time();
            }
        }

        // 5) อัปเดตฟิลด์จากโปรไฟล์ที่สนใจ
        $user->first_name        = $profile['first_name']        ?? $user->first_name;
        $user->last_name         = $profile['last_name']         ?? $user->last_name;
        $user->email             = $profile['email']             ?? $user->email;
        $user->img               = $profile['img']               ?? $user->img;
        $user->manage_faculty_id = $profile['manage_faculty_id'] ?? $user->manage_faculty_id;
        $user->dept_id           = $profile['dept_id']           ?? $user->dept_id;

        // เก็บ token ไว้ด้วยถ้าตารางคุณมีคอลัมน์นี้
        if ($user->hasAttribute('hrm_token')) {
            $user->hrm_token = $token;
        }

        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = time();
        }

        // 6) บันทึกลงฐานข้อมูล
        // ถ้าตอนนี้ rules() ยังไม่ครบ ให้ใช้ save(false) ไปก่อน
        if (!$user->save(false)) {
            return ['ok' => false, 'error' => 'cannot save user'];
        }

        // 7) login เข้า Yii
        Yii::$app->user->login($user, 0);

        // 8) เก็บ profile ไว้ใน session เผื่อ view ต้องใช้
        Yii::$app->session->set('hrmProfile', $profile);

        return [
            'ok'     => true,
            'userId' => $user->id ?? null,
            'user'   => [
                'personal_id'       => $user->personal_id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'email'             => $user->email,
                'img'               => $user->img,
                'manage_faculty_id' => $user->manage_faculty_id,
                'dept_id'           => $user->dept_id,
            ],
        ];
    }

    public function actionLogout()
    {
        // ล็อกเอาต์ + ทำลาย session + หมุน CSRF
        Yii::$app->user->logout(true);
        if (Yii::$app->session->isActive) {
            Yii::$app->session->destroy();
            Yii::$app->session->open();
            Yii::$app->session->regenerateID(true);
        }
        Yii::$app->request->getCsrfToken(true);

        return $this->goHome(); // ไป /site/index
    }

    public function actionContact()
    {
        return $this->render('contact');
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
