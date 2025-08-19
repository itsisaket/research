<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\httpclient\Client;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
        ];
    }

    public function login()
    {
        try {
            $client = new Client(['baseUrl' => Yii::$app->request->hostInfo]);

            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl(['auth/login-api'])  // ✅ ควรใช้ API โดยเฉพาะ ไม่ใช้ login HTML
                ->setData([
                    'uname' => $this->username,
                    'pwd' => $this->password,
                ])
                ->send();
            Yii::info("RAW response content: " . $response->content, __METHOD__);
            if ($response->isOk && isset($response->data['status']) && $response->data['status'] === 'success') {
                Yii::$app->session->set('jwt_token', $response->data['token']);
                Yii::$app->session->setFlash('success', 'Login success');
                return true;
            }

            $message = $response->data['message'] ?? 'Login failed';
            $this->addError('password', $message);
        } catch (\Throwable $e) {
            Yii::error("Login API error: " . $e->getMessage(), __METHOD__);
            $this->addError('password', 'Unable to connect to authentication server.');
        }

        return false;
    }
}
