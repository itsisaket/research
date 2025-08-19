<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'index'],
                'rules' => [
                    ['allow' => true, 'roles' => ['@']], // ต้องล็อกอินก่อน
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
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        // ลบคุกกี้ SSO ทั้งแบบไม่มีโดเมนและแบบโดเมนร่วม (กันพลาด)
        Yii::$app->response->cookies->remove('hrm-sci-token');
        Yii::$app->response->cookies->remove(new \yii\web\Cookie([
            'name' => 'hrm-sci-token',
            'domain' => '.sci-sskru.com',
            'path' => '/',
        ]));

        Yii::$app->session->remove('identity');
        Yii::$app->user->logout(true);
        Yii::$app->session->regenerateID(true);
        return $this->goHome();
    }

    public function actionPingAuth($u = '3331000521623', $p = '3331000521623')
    {
        $res = Yii::$app->apiClient->createRequest()
            ->setMethod('POST')
            ->setUrl('/authen/login')
            ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
            ->setData(['uname' => (string)$u, 'pwd' => (string)$p])
            ->send();

        $data = $res->getData();
        $claims = [];
        if (is_array($data ?? null) && !empty($data['token'])) {
            $parts = explode('.', $data['token']);
            if (count($parts) >= 2) {
                $payload = $parts[1] . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
                $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            }
        }

        return $this->asJson([
            'http_ok' => $res->isOk,
            'http_status' => $res->statusCode,
            'has_token' => !empty($data['token']),
            'claims' => $claims,
        ]);
    }
    public function actionMyProfile()
    {
        // ดึงโปรไฟล์ของผู้ใช้ปัจจุบัน (ใช้ token + personal_id จาก JWT)
        $profile = \Yii::$app->apiAuth->getMyProfile();
        return $this->render('my-profile', ['profile' => $profile]);
    }

}