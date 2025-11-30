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
        // ใช้เฉพาะข้อมูลที่ browser ส่งมา ถ้าเรียก API ไม่สำเร็จ
    }

    // 3) แปลง token + profile เป็น user object ชั่วคราว
    $jwtUser = User::fromToken($token, $profile);

    // 3.1 กำหนด username ที่จะใช้ในระบบเรา
    //    - พยายามใช้ username จาก JWT ก่อน
    //    - ถ้าไม่มี → ใช้ personal_id แทน
    $username = $jwtUser->username ?? $personalId;

    if (!$username) {
        return ['ok' => false, 'error' => 'profile has no username / personal_id'];
    }

    // 4) หา user เดิมจาก DB ด้วย username
    $account = Account::findOne(['username' => $username]);
    if ($account === null) {
        // ❗ เคส "ไม่มี username" → เพิ่มใหม่
        $account = new Account(['scenario' => 'ssoSync']);
        $account->username = $username;
        // กำหนดค่า default บางอย่างตอนสร้างใหม่ (ถ้าต้องการ)
        $account->position = 1; // สิทธิ์พื้นฐานเริ่มต้น
    } else {
        // ❗ เคส "มี username แล้ว" → ปรับปรุงข้อมูลจาก JWT
        $account->scenario = 'ssoSync';

        // กันกรณีฐานเดิมไม่มี position
        if ($account->position === null) {
            $account->position = 1;
        }
    }

    // 5) Map ข้อมูลจาก SSO → ตาราง tb_user (อัปเดตทุกครั้งที่ login)
    $account->prefix    = $jwtUser->prefix ?: 0;  // ถ้า prefix เป็นรหัสตัวเลข
    $account->uname     = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ');
    $account->luname    = $jwtUser->luname ?: '';
    $account->org_id    = $jwtUser->faculty_id ?: 0;
    $account->dept_code = $jwtUser->dept_code ?: 0;  
    $account->email     = $jwtUser->email ?: '';
    $account->tel       = $jwtUser->tel ?? '';

    // 6) บันทึกข้อมูล
    try {
        if (!$account->save()) {
            return [
                'ok'     => false,
                'error'  => 'validate fail',
                'detail' => $account->getErrors(),
            ];
        }
    } catch (\Throwable $e) {
        Yii::$app->errorHandler->logException($e);
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
            'ok'      => false,
            'error'   => 'login error',
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
        'userId' => $account->u_id ?? null,   // แก้ให้ตรงกับชื่อ PK ในตารางจริง
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
