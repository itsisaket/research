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
    $user = Yii::$app->user;

    // 1) ถ้าล็อกอินแล้ว → ไปหน้า report
    if (!$user->isGuest) {
        return $this->redirect(['report/index']);
    }

    $request = Yii::$app->request;

    // 2) ยังไม่ล็อกอิน + ถ้ามาแบบ POST แสดงว่ามาจาก JS ส่ง token มาให้
    if ($request->isPost) {
        $token = $request->post('token');

        if ($token) {
            Yii::$app->session->setFlash('info', 'พบ token → กำลังนำไปยืนยันตัวตนที่หน้า Login');
            return $this->redirect(['site/login']);
        }

        // ถ้า POST มาแต่ไม่มี token → ปล่อยเป็น Guest ไป report
        Yii::$app->session->setFlash('warning', 'ไม่พบ token → เข้าหน้า report ในฐานะ Guest');
        return $this->redirect(['report/index']);
    }

    // 3) ยังเป็น Guest + เป็น GET ธรรมดา → ให้ render view (JS จะไปเช็ค localStorage เอง)
    return $this->render('index', [
        'isGuest' => $user->isGuest,
        'u'       => $user->identity,
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

    $session = Yii::$app->session;

    // 0) จำกัดขนาด body กันยิง payload ใหญ่เกินไป
    $raw = Yii::$app->request->getRawBody();
    if (strlen($raw) > self::MAX_BODY_BYTES) {
        $session->setFlash('warning', 'ไม่สามารถ sync ได้: ข้อมูลที่ส่งมามีขนาดใหญ่เกินกำหนด');
        return [
            'ok'    => false,
            'error' => 'payload too large',
        ];
    }

    // 1) รับ JSON / POST จาก browser
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = Yii::$app->request->post();
    }

    $token   = $data['token']   ?? null;
    $profile = $data['profile'] ?? [];

    if (!$token) {
        $session->setFlash('warning', 'ไม่สามารถ sync ได้: ไม่พบ token จาก HRM-SCI');
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
        // ใช้ profile เท่าที่ browser ส่งมา
        $session->setFlash('warning', 'ไม่สามารถดึงข้อมูลโปรไฟล์จาก HRM ได้ จะใช้ข้อมูลเท่าที่มีจาก browser');
    }

    // 3) แปลง token + profile เป็น user object ชั่วคราวจาก JWT
    try {
        $jwtUser = User::fromToken($token, $profile);
    } catch (\Throwable $e) {
        Yii::error('User::fromToken failed: ' . $e->getMessage(), 'sso.sync');
        $session->setFlash('danger', 'ไม่สามารถแปลงข้อมูล token เป็นผู้ใช้ได้');
        return [
            'ok'      => false,
            'error'   => 'fromToken error',
            'message' => $e->getMessage(),
        ];
    }

    // 3.1 หาค่า username ที่จะใช้ในระบบเรา
    //     - พยายามใช้ username จาก JWT ก่อน
    //     - ถ้าไม่มี → ใช้ personal_id (รหัส 13 หลัก) แทน
    $username = $jwtUser->username ?? $personalId;

    if (!$username) {
        $session->setFlash('danger', 'โปรไฟล์จาก SSO ไม่มี username/personal_id ไม่สามารถสร้างบัญชีผู้ใช้ได้');
        return ['ok' => false, 'error' => 'profile has no username/personal_id'];
    }

    // 4) หา user เดิมจาก DB ด้วย username
    //    - ถ้าไม่มี → สร้างใหม่
    //    - ถ้ามี → อัปเดตข้อมูลจาก JWT
    $account = Account::findOne(['username' => $username]);
    if ($account === null) {
        // เคส "ยังไม่เคย sync" → เพิ่มใหม่
        $account = new Account();
        $account->scenario = 'ssoSync';
        $account->username = $username;

        $session->setFlash('info', "กำลังสร้างบัญชีผู้ใช้ใหม่จาก SSO สำหรับผู้ใช้: {$username}");
    } else {
        // เคส "เคยมีอยู่แล้ว" → ปรับปรุงข้อมูลตาม JWT ล่าสุด
        $account->scenario = 'ssoSync';
        $session->setFlash('info', "กำลังอัปเดตข้อมูลผู้ใช้จาก SSO สำหรับผู้ใช้: {$username}");
    }

    // 5) Map ข้อมูลจาก SSO / JWT → tb_user
    $account->prefix    = $jwtUser->prefix ?: 0; // ถ้า prefix เป็นรหัสตัวเลข
    $account->uname     = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ');
    $account->luname    = $jwtUser->luname ?: '';
    $account->org_id    = (int)($jwtUser->faculty_id ?? 0);
    $account->dept_code = (int)($jwtUser->dept_code ?? 0);
    $account->email     = $jwtUser->email ?: '';
    $account->tel       = $jwtUser->tel ?? '';

    // 5.1 ตั้งค่าพื้นฐานกรณี SSO (position, authKey, กันค่า null)
    $account->initDefaultsForSso();

    // 6) บันทึกข้อมูลลงฐาน
    try {
        if (!$account->save()) {
            Yii::error(
                'SSO sync validate fail: ' . json_encode($account->getErrors(), JSON_UNESCAPED_UNICODE),
                'sso.sync'
            );

            $session->setFlash('danger', 'บันทึกข้อมูลผู้ใช้จาก SSO ไม่สำเร็จ เนื่องจากข้อมูลไม่ผ่านการตรวจสอบ');

            return [
                'ok'     => false,
                'error'  => 'validate fail',
                'detail' => $account->getErrors(),
            ];
        }
    } catch (\Throwable $e) {
        Yii::error('SSO sync DB error: ' . $e->getMessage(), 'sso.sync');

        $session->setFlash('danger', 'เกิดข้อผิดพลาดในการบันทึกฐานข้อมูลผู้ใช้จาก SSO');

        return [
            'ok'      => false,
            'error'   => 'db error',
            'message' => $e->getMessage(),
        ];
    }

    // 7) Login เข้า Yii (ใช้ SESSION_DURATION ที่ประกาศใน controller)
    try {
        Yii::$app->user->login($account, self::SESSION_DURATION);
    } catch (\Throwable $e) {
        Yii::error('Login failed: ' . $e->getMessage(), 'sso.sync');

        $session->setFlash('danger', 'เข้าสู่ระบบด้วยบัญชีที่สร้าง/อัปเดตจาก SSO ไม่สำเร็จ');

        return [
            'ok'      => false,
            'error'   => 'login error',
            'message' => $e->getMessage(),
        ];
    }

    // 8) เก็บ token + profile ใน session (เผื่อใช้ที่อื่น)
    $session->set('hrmToken', $token);
    $session->set('hrmProfile', $profile);
    $session->set('ty', $account->org_id);

    // 9) Success → flash แจ้ง และส่งกลับให้ frontend
    $session->setFlash('success', 'เชื่อมต่อบัญชี HRM-SCI กับระบบงานวิจัยสำเร็จแล้ว');

    return [
        'ok'     => true,
        'userId' => $account->u_id,   // 🔁 แก้จาก uid → u_id ให้ตรง field
        'user'   => [
            'username'  => $account->username,
            'prefix'    => $account->prefix,
            'uname'     => $account->uname,
            'luname'    => $account->luname,
            'org_id'    => $account->org_id,
            'dept_code' => $account->dept_code,
            'email'     => $account->email,
            'tel'       => $account->tel,
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
            
        }
        Yii::$app->session->setFlash('warning', 'กำลังออกจากระบบ เป็นฐานะ Guest');
        Yii::$app->request->getCsrfToken(true);
        return $this->goHome();
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
