<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class ApiAuthService extends Component
{
    public $profileUrl;   // e.g. https://sci-sskru.com/authen/profile

    public function init()
    {
        parent::init();
        if (!$this->profileUrl) {
            $this->profileUrl = Yii::$app->params['ssoProfileUrl'] ?? '';
        }
    }

    /** เรียก SSO เพื่อดึงโปรไฟล์ (และถือเป็นการ validate โทเค็นด้วย) */
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
        // บางระบบห่อ { profile: {...} }
        if (isset($data['profile']) && is_array($data['profile'])) return $data['profile'];
        return is_array($data) ? $data : null;
    }
}
