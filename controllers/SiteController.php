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
use yii\httpclient\Client;


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
                        'actions' => ['index', 'login', 'error', 'about', 'my-profile','up-user-json'],
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

        try {
            // 0) จำกัดขนาด body ป้องกัน payload ใหญ่เกิน
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
            $profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];

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
            $username = $jwtUser->username ?? $personalId;
            if (!$username) {
                $session->setFlash('danger', 'โปรไฟล์จาก SSO ไม่มี username/personal_id ไม่สามารถสร้างบัญชีผู้ใช้ได้');
                return ['ok' => false, 'error' => 'profile has no username/personal_id'];
            }

            // 4) หา user เดิมจาก DB ด้วย username
            $account = Account::findOne(['username' => $username]);
            if ($account === null) {
                // ยังไม่เคย sync → สร้างใหม่
                $account = new Account();
                $account->scenario = 'ssoSync';
                $account->username = $username;
                $session->setFlash('info', "กำลังสร้างบัญชีผู้ใช้ใหม่จาก SSO สำหรับผู้ใช้: {$username}");
            } else {
                // เคยมี → อัปเดต
                $account->scenario = 'ssoSync';
                $session->setFlash('info', "กำลังอัปเดตข้อมูลผู้ใช้จาก SSO สำหรับผู้ใช้: {$username}");
            }

            // 5) Map ข้อมูลจาก SSO / JWT → tb_user
            $account->prefix = $jwtUser->prefix ?: 0;
            $account->uname  = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ');
            $account->luname = $jwtUser->luname ?: '';

            // ✅ ใช้ค่าจาก profile เป็นหลัก (มาจาก HRM)
            $facultyId = $profile['faculty_id'] ?? ($jwtUser->faculty_id ?? 0);
            $deptCode  = $profile['dept_code']  ?? ($jwtUser->dept_code  ?? 0);

            $account->org_id    = (int)$facultyId;
            $account->dept_code = (int)$deptCode;

            $account->email = $jwtUser->email ?: '';
            $account->tel   = $jwtUser->tel ?? '';

            // 5.1 ตั้งค่าพื้นฐานกรณี SSO (position, authKey, status, password_hash ฯลฯ)
            try {
                $account->initDefaultsForSso();
            } catch (\Throwable $e) {
                Yii::error('initDefaultsForSso error: ' . $e->getMessage(), 'sso.sync');
                return [
                    'ok'      => false,
                    'error'   => 'initDefaultsForSso error',
                    'message' => $e->getMessage(),
                ];
            }

            // 6) บันทึกข้อมูลลงฐาน
            try {
                if (!$account->save()) {
                    Yii::error(
                        'SSO sync validate fail for username=' . $account->username
                        . ' data=' . json_encode($account->attributes, JSON_UNESCAPED_UNICODE)
                        . ' errors=' . json_encode($account->getErrors(), JSON_UNESCAPED_UNICODE),
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

            // 7) Login เข้า Yii
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

            // 8) เก็บ token + profile ใน session
            $session->set('hrmToken', $token);
            $session->set('hrmProfile', $profile);
            $session->set('ty', $account->org_id);

            // 9) สำเร็จ
            $session->setFlash('success', 'เชื่อมต่อบัญชี HRM-SCI กับระบบงานวิจัยสำเร็จแล้ว');

            return [
                'ok'     => true,
                'userId' => $account->uid,
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

        } catch (\Throwable $e) {
            // ❌ อะไรที่ไม่ถูก try/catch ข้างในครอบไว้ จะมาเข้าอันนี้ → ป้องกัน 500
            Yii::error(
                'my-profile FATAL: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                'sso.sync'
            );

            // ให้ตอบ 200 พร้อม JSON error กลับไปแทน 500
                return [
                    'ok'      => false,
                    'error'   => 'fatal',
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => explode("\n", $e->getTraceAsString()),
                ];
        }
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

    public function actionUpUserJson($personal_id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $session = Yii::$app->session;

        try {
            // เรียก API
            $client = new \yii\httpclient\Client(['transport' => 'yii\httpclient\CurlTransport']);
            $apiUrl = 'https://sci-sskru.com/authen/list-profiles';

            $params = [];
            if (!empty($personal_id)) {
                $params['personal_id'] = $personal_id;
            }

            $response = $client->get($apiUrl, $params)->send();

            if (!$response->isOk) {
                $session->setFlash('danger', "เชื่อมต่อ API ไม่สำเร็จ (status: {$response->statusCode})");
                return $this->redirect(['site/about']);
            }

            $json = $response->getData();

            if (!isset($json['data']) || !is_array($json['data']) || count($json['data']) === 0) {
                $session->setFlash('warning', 'ไม่พบข้อมูลบุคลากรจากระบบ HRM');
                return $this->redirect(['site/about']);
            }

            // LOOP ทุกคน
            $total   = count($json['data']);
            $success = 0;
            $failed  = 0;

            foreach ($json['data'] as $profile) {
                $username = $profile['personal_id'] ?? null;
                if (empty($username)) {
                    $failed++;
                    continue;
                }

                $account = Account::findOne(['username' => $username]);
                $isNew   = false;

                if ($account === null) {
                    $account = new Account();
                    $account->scenario = 'ssoSync';
                    $account->username = $username;
                    $isNew = true;
                } else {
                    $account->scenario = 'ssoSync';
                }

                // Map HRM → tb_user
                $account->prefix = 0;
                $account->uname  = $profile['first_name'] ?? 'ไม่ระบุชื่อ';
                $account->luname = $profile['last_name']  ?? '';
                $account->org_id = (int)($profile['faculty_id'] ?? 0);
                $account->dept_code = (int)($profile['dept_code'] ?? 0);

                if ($account->email === null) $account->email = '';
                if ($account->tel   === null) $account->tel = '';

                try {
                    $account->initDefaultsForSso();
                } catch (\Throwable $e) {
                    $failed++;
                    continue;
                }

                if (!$account->save()) {
                    $failed++;
                    continue;
                }

                $success++;
            }

            // Flash message สรุป
            if ($failed === 0) {
                $session->setFlash('success', "อัปเดตข้อมูลบุคลากรสำเร็จทั้งหมด {$success} รายการ");
            } else {
                $session->setFlash('warning', "Sync เสร็จสิ้น: สำเร็จ {$success} รายการ, ล้มเหลว {$failed} รายการ");
            }

            return $this->redirect(['site/about']);

        } catch (\Throwable $e) {
            $session->setFlash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
            return $this->redirect(['site/about']);
        }
    }



 
}
