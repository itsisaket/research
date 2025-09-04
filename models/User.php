<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * Lightweight Identity จาก JWT + โปรไฟล์ /authen/profile
 */
class User implements IdentityInterface
{
    // Core
    public $id;                 // personal_id (PK)
    public $username;           // uname หรือ personal_id
    public $name;
    public $email;
    public $roles = [];
    public $access_token;

    // JWT times
    public $exp;
    public $iat;

    // โปรไฟล์จาก /authen/profile
    public $profile = [];

    /** ===== IdentityInterface ===== */
    public static function findIdentity($id)
    {
        $data = Yii::$app->session->get('_identity_data');
        if (!$data || !isset($data['id']) || (string)$data['id'] !== (string)$id) {
            return null;
        }
        return self::fromArray($data);
    }
    public static function findIdentityByAccessToken($token, $type = null){ return null; }
    public function getId(){ return $this->id; }
    public function getAuthKey(){ return null; }
    public function validateAuthKey($authKey){ return true; }

    /** ===== Factory & Helpers ===== */
    public static function fromToken(string $jwt, array $profile = null): self
    {
        $claims = self::decodeJwtPayload($jwt);

        $u = new self();
        $u->id       = $claims['personal_id'] ?? $claims['uname'] ?? null;
        $u->username = $claims['uname'] ?? $claims['personal_id'] ?? null;
        $u->name     = $claims['name'] ?? null;
        $u->email    = $claims['email'] ?? null;
        $u->roles    = $claims['roles'] ?? [];
        $u->exp      = $claims['exp'] ?? null;
        $u->iat      = $claims['iat'] ?? null;

        $u->profile      = is_array($profile) ? $profile : [];
        $u->access_token = $jwt;

        // เก็บสำเนาใน session ให้ findIdentity กู้คืนได้
        Yii::$app->session->set('_identity_data', $u->toArray());
        return $u;
    }

    public static function fromArray(array $arr): self
    {
        $u = new self();
        foreach ($arr as $k => $v) {
            if (property_exists($u, $k)) { $u->$k = $v; }
        }
        return $u;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'username'     => $this->username,
            'name'         => $this->name,
            'email'        => $this->email,
            'roles'        => $this->roles,
            'access_token' => $this->access_token,
            'exp'          => $this->exp,
            'iat'          => $this->iat,
            'profile'      => $this->profile,
        ];
    }

    public static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return [];
        $payload = self::b64urlDecode($parts[1]);
        $json = json_decode($payload, true);
        return is_array($json) ? $json : [];
    }

    private static function b64urlDecode(string $str): string
    {
        $str = strtr($str, '-_', '+/');
        $pad = strlen($str) % 4;
        if ($pad) $str .= str_repeat('=', 4 - $pad);
        $bin = base64_decode($str);
        return $bin === false ? '' : $bin;
    }
}
