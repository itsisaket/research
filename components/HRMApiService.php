<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class HRMApiService extends Component
{
    public $baseUrl = 'https://sci-sskru.com/hrm-api/v1';
    public $token;

    public function login($username, $password)
    {
        $client = new Client(['baseUrl' => $this->baseUrl]);
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('login')
            ->setData([
                'username' => $username,
                'password' => $password,
            ])
            ->send();

        if ($response->isOk && isset($response->data['token'])) {
            $this->token = $response->data['token'];
            Yii::$app->session->set('hrm_token', $this->token);
            return true;
        }

        return false;
    }

    public function getProfile()
    {
        $token = $this->token ?? Yii::$app->session->get('hrm_token');

        if (!$token) {
            throw new \Exception("Token not found");
        }

        $client = new Client(['baseUrl' => $this->baseUrl]);
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl('profile')
            ->addHeaders(['Authorization' => 'Bearer ' . $token])
            ->send();

        if ($response->isOk) {
            return $response->data;
        }

        return null;
    }
}
