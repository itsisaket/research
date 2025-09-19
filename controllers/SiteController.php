<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User; // <-- สำคัญ: ใช้แนวทางที่ 1 ต้องมี User::fromToken()

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // ควบคุมเฉพาะ 3 action นี้เท่านั้น
                'only' => ['login', 'my-profile', 'logout'],
                'rules' => [
                    [
                        'actions' => ['login', 'my-profile'],
                        'allow' => true,
                        'roles' => ['?', '@'], // guest + user
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],      // เฉพาะผู้ล็อกอิน
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'my-profile' => ['POST'],
                    'logout'     => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        // เรนเดอร์ views/site/login.php
        return $this->render('login');
    }

    /**
     * POST /site/my-profile
     * รับ token + profile จากฝั่ง JS เพื่อล็อกอินฝั่ง PHP (เซสชัน)
     * Body JSON: { token: string, profile: object }
     */
    public function actionMyProfile(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $body    = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $token   = (string)($body['token'] ?? '');
        $profile = is_array($body['profile'] ?? null) ? $body['profile'] : [];

        if ($token === '') {
            return ['ok' => false, 'error' => 'missing token'];
        }

        // สร้าง identity (รองรับ {profile:{...}} อยู่ใน normalizeProfile แล้ว)
        $identity = User::fromToken($token, $profile);

        // ล็อกอินเข้า Yii user component จริง ๆ (เช่น 14 วัน)
        Yii::$app->user->login($identity, 60 * 60 * 24 * 14);

        return ['ok' => true, 'id' => $identity->id];
    }

    public function actionLogout()
    {
        Yii::$app->user->logout(true);           // เคลียร์ identity + session
        Yii::$app->session->remove('_identity_data');
        return $this->goHome();
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
