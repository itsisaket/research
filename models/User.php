<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

class User implements IdentityInterface
{
    /** Core identity (มาจาก JWT Claims) */
    public $id;                 // personal_id หรือ uname
    public $username;           // uname
    public $name;               // ชื่อสำรอง (ถ้ามี)
    public $email;
    public $roles = [];
    public $access_token;       // เก็บ JWT ที่ใช้ login ครั้งนี้

    /** JWT times */
    public $exp;                // unix timestamp
    public $iat;                // unix timestamp

    /** โปรไฟล์เต็มจาก SSO */
    public $profile = [];       // ต้องเป็น array เสมอ

    /** ===== IdentityInterface (จำเป็น) ===== */
    public static function findIdentity($id) {
        // สำหรับ session-based เท่านั้น (ไม่ได้ค้น DB)
        $u = Yii::$app->session->get('__jwt_user__');
        if ($u && isset($u['id']) && (string)$u['id'] === (string)$id) {
            return self::fromArray($u);
        }
        return null;
    }
    public static function findIdentityByAccessToken($token, $type = null) { return null; }
    public function getId() { return $this->id; }
    public function getAuthKey() { return null; }
    public function validateAuthKey($authKey) { return true; }

    /** ===== Helper ===== */
    public static function fromClaims(array $claims, string $jwt, array $profile = null): self
    {
        $u = new self();
        $u->id       = $claims['personal_id'] ?? $claims['uname'] ?? null;
        $u->username = $claims['uname'] ?? $claims['personal_id'] ?? null;
        $u->name     = $profile['title_name'].' '.$profile['first_name'].' '.$profile['last_name']
                       ?? ($claims['name'] ?? ($claims['uname'] ?? ''));
        $u->email    = $profile['email'] ?? ($claims['email'] ?? null);
        $u->roles    = $claims['roles'] ?? [];
        $u->access_token = $jwt;

        $u->iat = isset($claims['iat']) ? (int)$claims['iat'] : null;
        $u->exp = isset($claims['exp']) ? (int)$claims['exp'] : null;

        $u->profile = is_array($profile) ? $profile : [];
        return $u;
    }

    public static function fromArray(array $arr): self
    {
        $u = new self();
        foreach ($arr as $k=>$v) { $u->$k = $v; }
        return $u;
    }

    /** base64url decode JWT payload */
    public static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return [];
        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $json = base64_decode(strtr($payload, '-_', '+/'));
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    public static function isExpired(array $claims): bool
    {
        if (!isset($claims['exp'])) return true;
        return time() >= (int)$claims['exp'];
    }

    /** เก็บลง session เพื่อใช้ findIdentity */
    public function persistToSession(): void
    {
        Yii::$app->session->set('__jwt_user__', [
            'id'           => $this->id,
            'username'     => $this->username,
            'name'         => $this->name,
            'email'        => $this->email,
            'roles'        => $this->roles,
            'access_token' => $this->access_token,
            'iat'          => $this->iat,
            'exp'          => $this->exp,
            'profile'      => $this->profile,
        ]);
    }
}
