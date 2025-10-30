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

public function actionMyProfile()
{
    Yii::$app->response->format = Response::FORMAT_JSON;

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

    // 2) ถ้า profile ที่ browser ส่งมายังไม่ครบ → ไปขอจาก API เพิ่ม
    $personalId = $profile['personal_id'] ?? null;
    try {
        /** @var \app\components\ApiAuthService|null $apiAuth */
        $apiAuth = Yii::$app->apiAuth ?? null;

        if ($apiAuth instanceof \app\components\ApiAuthService) {
            if ($personalId) {
                $full = $apiAuth->fetchProfileWithPost($token, $personalId);
            } else {
                $full = $apiAuth->fetchProfileByToken($token);
            }
        } else {
            // fallback static
            $full = \app\components\ApiAuthService::fetchProfileByToken($token);
        }

        if (is_array($full) && !empty($full)) {
            $profile    = $full;
            $personalId = $profile['personal_id'] ?? $personalId;
        }
    } catch (\Throwable $e) {
        Yii::warning('Fetch profile failed: ' . $e->getMessage(), 'sso');
        // ใช้ $profile จาก client ต่อ
    }

    // 3) ต้องมี personal_id แล้ว ไม่งั้นสร้าง user ไม่ได้
    if (!$personalId) {
        return ['ok' => false, 'error' => 'profile has no personal_id'];
    }

    // 4) แปลง token + profile เป็น object ชั่วคราว (ของคุณเอง)
    //    สมมติคลาส User มีเมธอดนี้
    $jwtUser = \app\models\User::fromToken($token, $profile);

    // 5) หาใน tb_user ด้วย username = personal_id
    /** @var \app\models\Account|null $account */
    $account = \app\models\Account::findOne(['username' => $personalId]);

    if ($account === null) {
        $account = new \app\models\Account(['scenario' => 'ssoSync']);
        $account->username = $personalId;
    } else {
        $account->scenario = 'ssoSync';
    }

    // 6) map ฟิลด์จาก SSO → tb_user
    $account->prefix   = $jwtUser->prefix ?: 0;
    $account->uname    = $jwtUser->uname ?: ($jwtUser->name ?? 'ไม่ระบุชื่อ');
    $account->luname   = $jwtUser->luname ?: '';
    $account->org_id   = $jwtUser->org_id ?: 0;
    $account->email    = $jwtUser->email ?: '';
    $account->position = 1; // ให้ active ไว้ก่อน

    // tel บางที SSO ไม่ส่งมา → กัน null
    if ($account->tel === null || $account->tel === '') {
        $account->tel = '';
    }

    // ถ้าคุณมี helper ตั้งค่า default อื่น ๆ ก็เรียกตรงนี้
    // $account->initDefaultsForSso();

    // 7) บันทึก
    try {
        if (!$account->save()) {
            // วาลิเดตไม่ผ่าน → ส่งรายละเอียดกลับไปเลย
            return [
                'ok'    => false,
                'error' => 'validate fail',
                'detail'=> $account->getErrors(),
            ];
        }
    } catch (\Throwable $e) {
        // เซฟชน DB (unique, length, not null ที่ DB, ฯลฯ)
        Yii::error($e->getMessage(), 'sso');
        return [
            'ok'      => false,
            'error'   => 'db error',
            'message' => $e->getMessage(),
        ];
    }

    // 8) login เข้า Yii
    Yii::$app->user->login($account, 60 * 60 * 8); // 8 ชั่วโมง

    // 9) เก็บ profile ไว้ใน session
    Yii::$app->session->set('hrmProfile', $profile);

    // 10) ส่งกลับไปให้ frontend
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
