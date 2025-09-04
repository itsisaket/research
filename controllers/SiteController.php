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
                // ระบุให้ชัดว่าควบคุม action เหล่านี้
                'only' => ['index', 'login', 'my-profile', 'logout'],
                'rules' => [
                    // login + my-profile ให้ guest เข้าถึงได้ (เพราะยังไม่ได้ล็อกอิน)
                    [
                        'actions' => ['login', 'my-profile'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    // หน้าแรกกับ logout ให้เฉพาะคนล็อกอินแล้ว (ปรับเป็น ['?','@'] ถ้าต้องการให้ Guest เข้า index ได้)
                    [
                        'actions' => ['index', 'logout'],
                        'allow' => true,
                        'roles' => ['@'],
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

        $data    = json_decode(Yii::$app->request->getRawBody(), true);
        $token   = $data['token']   ?? null;
        $profile = $data['profile'] ?? null;

        if (!$token) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'message' => 'token is required'];
        }

        $identity = User::fromToken($token, is_array($profile) ? $profile : null);
        if (!$identity->id) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'message' => 'invalid token (no personal_id/uname)'];
        }

        // ล็อกอินเป็นเวลา 8 ชั่วโมง (ปรับได้)
        if (Yii::$app->user->login($identity, 60 * 60 * 8)) {
            Yii::$app->session->set('identity', $identity->toArray());
            return ['ok' => true];
        }

        Yii::$app->response->statusCode = 500;
        return ['ok' => false, 'message' => 'unable to login'];
    }

    public function actionLogout()
    {
        Yii::$app->user->logout(false);
        Yii::$app->session->remove('_identity_data');
        Yii::$app->session->remove('identity');
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
