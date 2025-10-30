<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;

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
        $u       = $isGuest ? null : Yii::$app->user->identity; // \app\models\User|null
        return $this->render('index', compact('isGuest','u'));
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
     * แล้ว login เข้า Yii + เก็บโปรไฟล์ไว้ใน session
     */
    public function actionMyProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // รับ JSON ดิบ
        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            // เผื่อกรณีส่งแบบ form
            $data = Yii::$app->request->post();
        }

        $token   = $data['token']   ?? null;
        $profile = $data['profile'] ?? [];

        if (!$token) {
            return ['ok' => false, 'error' => 'no token'];
        }

        // หาผู้ใช้จาก token
        $user = User::findByHrmToken($token);
        if (!$user) {
            return ['ok' => false, 'error' => 'user not found (token not mapped)'];
        }

        // login เข้า Yii
        Yii::$app->user->login($user, 3600 * 8);

        // เก็บโปรไฟล์ไว้ใช้ต่อ
        Yii::$app->session->set('hrmProfile', $profile);

        return ['ok' => true];
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
