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

    const BASE_URL = 'https://sci-sskru.com';

    public static function fetchProfileByToken(string $token): ?array
    {
        $url = self::BASE_URL . '/authen/profile';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json",
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([]), // ถ้า API ไม่ได้ใช้ body ก็ส่งว่าง ๆ ไป
            CURLOPT_TIMEOUT => 10,
        ]);

        $res  = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($info['http_code'] !== 200) {
            // ลอง GET อีกแบบ เผื่อ backend คุณตั้งแบบนี้
            $url = self::BASE_URL . '/authen/profile';
            $url .= '?_=' . time(); // กัน cache
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$token}",
                ],
                CURLOPT_TIMEOUT => 10,
            ]);
            $res  = curl_exec($ch);
            $info = curl_getinfo($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($info['http_code'] !== 200) {
                return null;
            }
        }

        $data = json_decode($res, true);
        return is_array($data) ? $data : null;
    }
}
