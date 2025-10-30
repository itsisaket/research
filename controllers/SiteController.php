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

    /**
     * ✅ แอ็กชันรับ sync จากหน้า login.js
     * รับ JSON: { "token": "...", "profile": {...} }
     * แล้ว login เข้า Yii + เก็บ/อัปเดตข้อมูลในตาราง user
     */
public function actionMyProfile()
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    // ... โค้ดเดิมของคุณด้านบน ...

    // map เสร็จแล้ว
    try {
        if (!$account->save()) {
            return [
                'ok' => false,
                'error' => 'validate fail',
                'detail' => $account->getErrors(),
            ];
        }
    } catch (\Throwable $e) {
        Yii::error($e->getMessage(), 'sso');
        return [
            'ok' => false,
            'error' => 'db error',
            'message' => $e->getMessage(),
        ];
    }

    Yii::$app->user->login($account, 60 * 60 * 8);
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
