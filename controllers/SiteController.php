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
                // ✅ อนุญาต avatar-proxy ให้ทุกคน (ต้องมาก่อน rule ทั่วไป)
                [
                    'actions' => ['avatar-proxy'],
                    'allow'   => true,
                    'roles'   => ['?', '@'],
                ],
                [
                    // ✅ เปิดให้ index/login/error/about/my-profile โดยไม่ต้องล็อกอิน
                    'actions' => ['index', 'login', 'error', 'about', 'my-profile'],
                    'allow'   => true,
                ],
                [
                    // ✅ logout ต้องล็อกอิน
                    'actions' => ['logout'],
                    'allow'   => true,
                    'roles'   => ['@'],
                ],
                // (ไม่มี rule อื่น แปลว่า action อื่นๆ จะถูก block ตามค่า default → 403)
            ],
        ],
        'verbs' => [
            'class'   => VerbFilter::class,
            'actions' => [
                'avatar-proxy' => ['GET'],  // ✅ รับเฉพาะ GET
                'my-profile'   => ['POST'],
                'logout'       => ['POST'],
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

    public function beforeAction($action)
    {
        if (in_array($action->id, ['avatar-proxy','login','error','about','index','my-profile'], true)) {
            return parent::beforeAction($action);
        }
        // ... logic เดิมของคุณ ...
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {

        $session = Yii::$app->session;
        if (!$session->get('first_check_done', false)) {
            $session->set('first_check_done', true);
            return $this->redirect(['site/login']);
        }
        // ถ้า login แล้ว → เข้าหน้ารายงาน
        return $this->redirect(['report/index']);
        


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
            
        }
        Yii::$app->request->getCsrfToken(true);
        return $this->goHome();
    }

    public function actionAvatarProxy($src)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;

        // 2.1 Allowlist โฮสต์ต้นทาง
        $allowedHosts = ['sci-sskru.com','www.sci-sskru.com'];
        $url = filter_var($src, FILTER_VALIDATE_URL) ? $src : null;
        if (!$url) {
            return $this->redirect(\yii\helpers\Url::to('@web/template/berry/images/user/avatar-2.jpg', true));
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!in_array($host, $allowedHosts, true)) {
            throw new \yii\web\BadRequestHttpException('Host not allowed'); // 400 แทน 403
        }

        // 2.2 ดึงรูป (ใช้ cURL จะชัวร์กว่า)
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'User-Agent: SES-AvatarProxy/1.0',
                'Referer:',
            ],
        ]);
        $data = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($data === false || $http >= 400) {
            // ต้นทางไม่ให้โหลด/ไม่มีไฟล์ → ใช้ fallback
            return $this->redirect(\yii\helpers\Url::to('@web/template/berry/images/user/avatar-2.jpg', true));
        }

        // 2.3 เดา mime จากนามสกุล
        $ext  = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $mime = [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
            'gif'=>'image/gif','webp'=>'image/webp','svg'=>'image/svg+xml'
        ][$ext] ?? 'image/jpeg';

        $res = \Yii::$app->response;
        $res->headers->set('Content-Type', $mime);
        $res->headers->set('Cache-Control', 'public, max-age=3600');
        return $data;
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
