<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;
use app\components\ApiAuthService;
use app\models\Account;  // ← ตัว AR ผูก tb_user

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

    // 1) รับ JSON จาก fetch(...)
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

    // 2) ถ้า profile ที่ browser ส่งมายังไม่ครบ → ไปขอจาก API เพิ่ม
    $personalId = $profile['personal_id'] ?? null;
    try {
        /** @var ApiAuthService|null $apiAuth */
        $apiAuth = Yii::$app->apiAuth ?? null;
        if ($apiAuth instanceof ApiAuthService) {
            if ($personalId) {
                // มี personal_id แล้ว → ยิงแบบ POST
                $full = $apiAuth->fetchProfileWithPost($token, $personalId);
            } else {
                // ไม่มี → ลองดึงจาก token ตรงๆ
                $full = $apiAuth->fetchProfileByToken($token);
            }
        } else {
            // กรณีไม่มีคอมโพเนนต์ ใช้ static (แล้วแต่โปรเจกต์คุณ)
            $full = \app\components\ApiAuthService::fetchProfileByToken($token);
        }

        if (is_array($full) && !empty($full)) {
            $profile = $full;
            $personalId = $profile['personal_id'] ?? $personalId;
        }
    } catch (\Throwable $e) {
        Yii::warning('Fetch profile failed: ' . $e->getMessage(), 'sso');
        // ใช้ profile จาก client ต่อไป
    }

    // ถึงตรงนี้ควรมี personal_id แล้ว
    if (!$personalId) {
        return ['ok' => false, 'error' => 'profile has no personal_id'];
    }

    // 3) ใช้ตัวช่วย stateless แปลง JWT + profile → object ชั่วคราว
    //    (ตัวนี้เราปรับไว้แล้วให้ map prefix/uname/luname/org_id)
    $jwtUser = User::fromToken($token, $profile);

    // 4) หาใน tb_user ด้วย username = personal_id
    /** @var Account|null $account */
    $account = Account::findOne(['username' => $personalId]);

    // 5) ถ้าไม่เจอ → สร้างใหม่
    if ($account === null) {
        $account = new Account(['scenario' => 'ssoSync']);
        $account->username = $personalId;
    } else {
        $account->scenario = 'ssoSync';
    }

    // 6) แมปฟิลด์จากโปรไฟล์เข้า tb_user
    //    ตามที่คุณใช้ใน model Account
    $account->prefix = $jwtUser->prefix ?: 0;  // prefix = title_id
    $account->uname  = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ'); // uname = first_name
    $account->luname = $jwtUser->luname ?: ''; // luname = last_name
    $account->org_id = $jwtUser->org_id ?: 0;  // org_id = manage_faculty_id
    $account->email  = $jwtUser->email ?: '';
    // กันกรณี tel เป็น required ใน rules
    if ($account->tel === null || $account->tel === '') {
        $account->tel = '';
    }

    // 7) ตั้งค่าเริ่มต้นจาก SSO → ตรงนี้จะ set position = 1 ครั้งแรก
    $account->initDefaultsForSso();

    // 8) บันทึก
    if (!$account->save(false)) {
        return ['ok' => false, 'error' => 'cannot save account'];
    }

    // 9) login เข้า Yii ด้วยตัวที่มาจาก DB (Account)
    Yii::$app->user->login($account, 60 * 60 * 8); // 8 ชม. แล้วแต่คุณจะตั้ง

    // 10) เก็บ profile ไว้ใน session เผื่อ view หน้าอื่นใช้
    Yii::$app->session->set('hrmProfile', $profile);

    return [
        'ok'     => true,
        'userId' => $account->uid,
        'user'   => [
            'username'  => $account->username,
            'prefix'    => $account->prefix,
            'uname'     => $account->uname,
            'luname'    => $account->luname,
            'org_id'    => $account->org_id,
            'email'     => $account->email,
            'position'  => $account->position, // จะเป็น 1 ถ้าสร้างใหม่
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
