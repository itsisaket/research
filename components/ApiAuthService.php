<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class ApiAuthService extends Component
{
    /** POST /authen/login -> token */
    public function login(string $username, string $password): string
    {
        try {
            $res = Yii::$app->apiClient->createRequest()
                ->setMethod('POST')
                ->setUrl('/authen/login') // baseUrl ดูที่ config/web.php
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders(['Accept' => 'application/json'])
                ->setData(['uname' => trim($username), 'pwd' => $password])
                ->send();
        } catch (\Throwable $e) {
            throw new \RuntimeException('เชื่อมต่อ Login API ไม่ได้: '.$e->getMessage(), 0, $e);
        }

        if (!$res->isOk) {
            throw new \RuntimeException('Login API HTTP '.$res->statusCode);
        }

        $data = $res->getData();
        $token = $data['token'] ?? null;
        if (!$token && isset($data['status']) && $data['status']==='ok') {
            $token = $data['token'] ?? null;
        }
        if (!$token) {
            throw new \DomainException('ไม่พบ token จากเซิร์ฟเวอร์');
        }
        return (string)$token;
    }

    /** POST /authen/profile -> ['profile'=>{...}] or root JSON */
    public function getProfileByPersonalId(string $personalId, bool $forceRefresh=false): array
    {
        $personalId = trim($personalId);
        if ($personalId === '') throw new \DomainException('ต้องระบุรหัสประจำตัวประชาชน');

        $ck = 'profile:'.$personalId;
        if (!$forceRefresh && Yii::$app->has('cache')) {
            $cached = Yii::$app->cache->get($ck);
            if (is_array($cached)) return $cached;
        }

        try {
            $res = Yii::$app->apiClient->createRequest()
                ->setMethod('POST')
                ->setUrl('/authen/profile')
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders(['Accept' => 'application/json'])
                ->setData(['personal_id' => $personalId])
                ->send();
        } catch (\Throwable $e) {
            throw new \RuntimeException('เชื่อมต่อ Profile API ไม่ได้: '.$e->getMessage(), 0, $e);
        }

        if ($res->statusCode === 401) {
            if (!Yii::$app->user->isGuest) {
                Yii::$app->user->logout(true);
                Yii::$app->session->remove('identity');
            }
            throw new \DomainException('เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');
        }
        if (!$res->isOk) {
            throw new \RuntimeException('Profile API HTTP '.$res->statusCode);
        }

        $data = $res->getData();
        $profile = (is_array($data['profile'] ?? null)) ? $data['profile'] : (is_array($data) ? $data : null);
        if (!is_array($profile) || !$profile) {
            throw new \DomainException('ไม่พบข้อมูลโปรไฟล์');
        }

        if (Yii::$app->has('cache')) Yii::$app->cache->set($ck, $profile, 300);
        return $profile;
    }

    /** ใช้ personal_id จากโทเค็นของผู้ใช้ปัจจุบัน + อัปเดต session['identity']['profile'] */
    public function getMyProfile(bool $forceRefresh=false): array
    {
        if (Yii::$app->user->isGuest) throw new \DomainException('กรุณาเข้าสู่ระบบ');
        /** @var \app\models\User $id */
        $id = Yii::$app->user->identity;

        if (property_exists($id, 'exp') && $id->exp && time() >= (int)$id->exp) {
            Yii::$app->user->logout(true);
            Yii::$app->session->remove('identity');
            throw new \DomainException('เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');
        }

        $personalId = (string)($id->id ?? $id->username ?? '');
        if ($personalId === '') throw new \DomainException('ไม่พบ personal_id ในโทเค็นผู้ใช้');

        $profile = $this->getProfileByPersonalId($personalId, $forceRefresh);

        $identityArr = Yii::$app->session->get('identity');
        if (!is_array($identityArr)) {
            $identityArr = method_exists($id, 'toArray') ? $id->toArray() : [];
        }
        $identityArr['profile'] = $profile;
        Yii::$app->session->set('identity', $identityArr);

        return $profile;
    }
}
