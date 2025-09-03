<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class ApiAuthService extends Component
{
    public $profileUrl;

    public function init(){ parent::init();
        $this->profileUrl = $this->profileUrl ?: (Yii::$app->params['ssoProfileUrl'] ?? '');
    }

    /** POST /authen/profile + Bearer <jwt> + body {personal_id} -> array|null */
    public function fetchProfileWithPost(string $jwt, string $personalId): ?array {
        if(!$this->profileUrl||!$jwt||!$personalId) return null;
        $client=new Client(['transport'=>'yii\httpclient\CurlTransport']);
        $resp=$client->createRequest()->setMethod('POST')->setUrl($this->profileUrl)
            ->addHeaders(['Authorization'=>'Bearer '.$jwt,'Content-Type'=>'application/json'])
            ->setContent(json_encode(['personal_id'=>$personalId],JSON_UNESCAPED_UNICODE))->send();
        if(!$resp->isOk) return null;
        $data=$resp->getData(); return isset($data['profile'])&&is_array($data['profile'])?$data['profile']:(is_array($data)?$data:null);
    }
}
