<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Account;
use app\components\ApiAuthService;

class SiteController extends Controller
{
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
                        // ✅ เปิดให้ my-profile ใช้ได้แม้ยังไม่ login (ใช้ตอน sync SSO)
                        'actions' => ['index', 'login', 'error', 'about', 'my-profile'],
                        'allow'   => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
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

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            return $this->render('error', [
                'name' => $exception->getName(),
                'message' => Yii::$app->params['showErrorDetail'] ? $exception->getMessage() : 'เกิดข้อผิดพลาดในระบบ',
            ]);
        }
    }

public function actionIndex()
{
    $session = Yii::$app->session;
    $user    = Yii::$app->user->identity;
    $isGuest = Yii::$app->user->isGuest;

    // ================================
    // 🔹 1) ตรวจสอบเฉพาะ "ครั้งแรก"
    // ================================
    $loginFlag = $session->get('login', 0);

    if ($loginFlag != 9) {
        // ตั้ง flag ว่า "ตรวจสอบแล้ว"
        $session->set('login', 9);

        // ✅ ส่งไปตรวจ login ที่ site/login (ครั้งแรกเท่านั้น)
        return $this->redirect(['site/login']);
    }

    // ================================
    // 🔹 2) ถ้าเคยตรวจแล้ว → แสดงหน้า index ปกติ
    // ================================
    $displayName  = null;
    $displayEmail = null;

    if (!$isGuest && $user) {
        $hrmProfile = $session->get('hrmProfile', []);

        $displayName =
            ($user->uname ?? null)
            ?: ($user->name ?? null)
            ?: trim(
                ($hrmProfile['title_name'] ?? '') . ' ' .
                ($hrmProfile['first_name'] ?? '') . ' ' .
                ($hrmProfile['last_name'] ?? '')
            )
            ?: ($user->username ?? null)
            ?: 'ไม่ระบุชื่อ';

        $displayEmail =
            ($user->email ?? null)
            ?: ($hrmProfile['email'] ?? null)
            ?: '-';
    }

    return $this->render('index', [
        'isGuest'      => $isGuest,
        'u'            => $user,
        'displayName'  => $displayName,
        'displayEmail' => $displayEmail,
    ]);
}



    /** ============================
     *  หน้า Login / SSO Auto-login
     * ============================ */
    public function actionLogin()
    {
        // ถ้าล็อกอินอยู่แล้ว → กลับหน้าแรก
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        // ลอง auto-login จาก cookie ของ SSO ถ้ามี
        try {
            Yii::$app->sso->tryAutoLoginFromCookie();
            if (!Yii::$app->user->isGuest) {
                return $this->goHome();
            }
        } catch (\Throwable $e) {
            Yii::warning('SSO auto-login failed: ' . $e->getMessage(), 'sso.sync');
        }

        return $this->render('login');
    }

    /** =====================================================
     * ✅ Action รับข้อมูลจากหน้า login.js เพื่อ sync token + profile
     * ===================================================== */
    public function actionMyProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // สำหรับระบบที่ frontend อยู่คนละโดเมน → เปิด CORS
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token');

        // 1) รับ JSON จาก browser
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

        // 2) ถ้า profile ยังไม่ครบ → ขอข้อมูลเต็มจาก API
        $personalId = $profile['personal_id'] ?? null;
        try {
            /** @var ApiAuthService|null $apiAuth */
            $apiAuth = Yii::$app->apiAuth ?? null;

            if ($apiAuth instanceof ApiAuthService) {
                $full = $personalId
                    ? $apiAuth->fetchProfileWithPost($token, $personalId)
                    : $apiAuth->fetchProfileByToken($token);
            } else {
                $full = ApiAuthService::fetchProfileByToken($token);
            }

            if (is_array($full) && !empty($full)) {
                $profile    = $full;
                $personalId = $profile['personal_id'] ?? $personalId;
            }
        } catch (\Throwable $e) {
            Yii::warning('Fetch profile failed: ' . $e->getMessage(), 'sso.sync');
            // ใช้ข้อมูลเท่าที่ browser ส่งมา
        }

        if (!$personalId) {
            return ['ok' => false, 'error' => 'profile has no personal_id'];
        }

        // 3) แปลง token + profile เป็น user object ชั่วคราว
        $jwtUser = User::fromToken($token, $profile);

        // 4) หา user เดิมจาก DB
        $account = Account::findOne(['username' => $personalId]);
        if ($account === null) {
            $account = new Account(['scenario' => 'ssoSync']);
            $account->username = $personalId;
        } else {
            $account->scenario = 'ssoSync';
        }

        // 5) Map ข้อมูลจาก SSO → ตาราง tb_user
        $account->prefix = $jwtUser->prefix ?: 0;
        $account->uname  = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ');
        $account->luname = $jwtUser->luname ?: '';
        $account->org_id = $jwtUser->org_id ?: 0;
        $account->email  = $jwtUser->email ?: '';
        $account->tel    = $jwtUser->tel ?? '';

        // position logic
        if ($account->isNewRecord) {
            // เพิ่มใหม่ → สิทธิ์พื้นฐาน
            $account->position = 1;
        } else {
            // มีใน DB แล้ว → ใช้ค่าที่มีอยู่
            // ถ้าจะไม่แตะเลยก็ไม่ต้องเซ็ตซ้ำ
            // ถ้าจะกัน null กรณีฐานข้อมูลเก่าให้ทำแบบนี้
            if ($account->position === null) {
                $account->position = 1;
            }
        }


        // 6) พยายามบันทึกข้อมูล
        try {
            if (!$account->save()) {
                return [
                    'ok'     => false,
                    'error'  => 'validate fail',
                    'detail' => $account->getErrors(),
                ];
            }
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'sso.sync');
            return [
                'ok'      => false,
                'error'   => 'db error',
                'message' => $e->getMessage(),
            ];
        }

        // 7) Login เข้า Yii (8 ชั่วโมง)
        try {
            Yii::$app->user->login($account, 60 * 60 * 8);
        } catch (\Throwable $e) {
            Yii::error('Login failed: ' . $e->getMessage(), 'sso.sync');
            return [
                'ok' => false,
                'error' => 'login error',
                'message' => $e->getMessage(),
            ];
        }

        // 8) เก็บ token + profile ใน session
        Yii::$app->session->set('hrmToken', $token);
        Yii::$app->session->set('hrmProfile', $profile);
        Yii::$app->session->set('ty', $account->org_id);

        // 9) ส่งกลับให้ frontend
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
                'position'  => $account->position,
            ],
        ];
    }

    /** ============================
     * Logout และเคลียร์ session
     * ============================ */
    public function actionLogout()
    {
        Yii::$app->user->logout(true);
        if (Yii::$app->session->isActive) {
            Yii::$app->session->destroy();
            Yii::$app->session->open();
            Yii::$app->session->regenerateID(true);
            Yii::$app->session->remove('login'); 
        }
        Yii::$app->request->getCsrfToken(true);
        return $this->goHome();
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
