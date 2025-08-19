<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class ApiAuthService extends Component
{
    /**
     * POST /authen/login  ->  returns ["token" => "..."]
     */
    public function login(string $username, string $password): string
    {
        $res = Yii::$app->apiClient->createRequest()
            ->setMethod('POST')
            ->setUrl('/authen/login')
            ->setFormat(Client::FORMAT_JSON)
            ->setData([
                'uname' => (string)trim($username),
                'pwd'   => (string)$password,
            ])
            ->send();

        if (!$res->isOk) {
            throw new \RuntimeException('Auth API HTTP '.$res->statusCode);
        }

        $data = $res->getData();
        if (!is_array($data) || empty($data['token'])) {
            throw new \DomainException('ไม่พบ token จากเซิร์ฟเวอร์');
        }
        return $data['token'];
    }

    /**
     * POST /authen/profile -> returns ["profile" => {...}]
     *
     * @throws \DomainException  เมื่อไม่พบข้อมูล/หมดอายุสิทธิ์
     * @throws \RuntimeException เมื่อมีปัญหา HTTP/เครือข่าย
     */
    public function getProfileByPersonalId(string $personalId, bool $forceRefresh = false): array
    {
        $personalId = trim($personalId);
        if ($personalId === '') {
            throw new \DomainException('ต้องระบุรหัสประจำตัวประชาชน');
        }

        $cacheKey = 'profile:' . $personalId;
        if (!$forceRefresh && Yii::$app->has('cache')) {
            $cached = Yii::$app->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
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
            throw new \RuntimeException('เชื่อมต่อเซิร์ฟเวอร์โปรไฟล์ไม่ได้: ' . $e->getMessage(), 0, $e);
        }

        // token หมดอายุ/ไม่ถูกต้อง
        if ($res->statusCode === 401) {
            if (!Yii::$app->user->isGuest) {
                Yii::$app->user->logout(true);
                Yii::$app->session->remove('identity');
            }
            throw new \DomainException('เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');
        }

        if (!$res->isOk) {
            throw new \RuntimeException('Profile API HTTP ' . $res->statusCode);
        }

        $data = $res->getData();
        $profile = null;

        if (is_array($data)) {
            if (isset($data['profile']) && is_array($data['profile'])) {
                $profile = $data['profile'];
            } else {
                // บางระบบอาจส่งโปรไฟล์เป็นราก JSON
                $profile = $data;
            }
        }

        if (!is_array($profile) || empty($profile)) {
            throw new \DomainException('ไม่พบข้อมูลโปรไฟล์');
        }

        if (Yii::$app->has('cache')) {
            Yii::$app->cache->set($cacheKey, $profile, 300); // แคช 5 นาที
        }

        return $profile;
    }

    /**
     * ใช้ personal_id จากโทเค็นของผู้ใช้ปัจจุบัน
     *
     * @throws \DomainException  เมื่อยังไม่ล็อกอิน/หมดอายุสิทธิ์/ไม่มี personal_id
     * @throws \RuntimeException เมื่อมีปัญหา HTTP/เครือข่าย
     */
    public function getMyProfile(bool $forceRefresh = false): array
    {
        if (Yii::$app->user->isGuest) {
            throw new \DomainException('กรุณาเข้าสู่ระบบ');
        }

        /** @var \app\models\User $id */
        $id = Yii::$app->user->identity;

        // ตรวจ token หมดอายุจาก exp ใน JWT (ถ้ามี)
        if (property_exists($id, 'exp') && !empty($id->exp) && time() >= (int)$id->exp) {
            Yii::$app->user->logout(true);
            Yii::$app->session->remove('identity');
            throw new \DomainException('เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');
        }

        $personalId = (string)($id->id ?? $id->username ?? '');
        if ($personalId === '') {
            throw new \DomainException('ไม่พบ personal_id ในโทเค็นผู้ใช้');
        }

        $profile = $this->getProfileByPersonalId($personalId, $forceRefresh);

        // อัปเดตลง session ให้ UI ส่วนอื่นใช้งานได้ทันที
        $identityArr = Yii::$app->session->get('identity');
        if (!is_array($identityArr)) {
            // ถ้ายังไม่เคยตั้งไว้ ลองแปลงจาก object identity
            $identityArr = method_exists($id, 'toArray') ? $id->toArray() : [];
        }
        $identityArr['profile'] = $profile;
        Yii::$app->session->set('identity', $identityArr);

        return $profile;
    }
 }