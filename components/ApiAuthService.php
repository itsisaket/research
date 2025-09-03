<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class ApiAuthService extends Component
{
    public $loginApi;    // POST uname/pwd -> {token}
    public $profileUrl;  // GET profile (optional)

    public function init()
    {
        parent::init();
        $this->loginApi  = $this->loginApi  ?: Yii::$app->params['ssoLoginApi']   ?? '';
        $this->profileUrl= $this->profileUrl?: Yii::$app->params['ssoProfileUrl'] ?? '';
    }

    /** เรียก SSO ด้วย uname/pwd → คืน string JWT หรือ null */
    public function login(string $uname, string $pwd): ?string
    {
        if (!$this->loginApi) return null;

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $resp = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->loginApi)
            ->addHeaders(['Content-Type' => 'application/json'])
            ->setContent(json_encode(['uname'=>$uname, 'pwd'=>$pwd]))
            ->send();

        if (!$resp->isOk) return null;

        $data = $resp->getData();
        return is_array($data) && !empty($data['token']) ? $data['token'] : null;
    }

    /** (ออปชัน) ดึงโปรไฟล์เพื่อยืนยันโทเค็น/เติมข้อมูล */
    public function fetchProfile(string $jwt): ?array
    {
        if (!$this->profileUrl) return null;

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $resp = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->profileUrl)
            ->addHeaders(['Authorization' => 'Bearer '.$jwt])
            ->send();

        if (!$resp->isOk) return null;
        $data = $resp->getData();
        return isset($data['profile']) && is_array($data['profile']) ? $data['profile']
             : (is_array($data) ? $data : null);
    }
}
