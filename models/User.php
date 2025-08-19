<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

class User implements IdentityInterface
{
    /** Core identity */
    public $id;                 // personal_id
    public $username;           // uname
    public $name;               // fallback name
    public $email;
    public $roles = [];
    public $access_token;

    /** JWT times */
    public $exp;                // unix timestamp
    public $iat;                // unix timestamp

    /** Profile payload from /authen/profile */
    public $profile = [];       // <-- สำคัญ: ให้เป็น "อาร์เรย์" เสมอ

    /**
     * สร้าง User จาก JWT (แนบ profile ได้ถ้ามี)
     */
    public static function fromToken(string $jwt, array $profile = null): self
    {
        $claims = self::decodeJwtPayload($jwt);

        $u = new self();
        $u->id       = $claims['personal_id'] ?? $claims['uname'] ?? null;
        $u->username = $claims['uname'] ?? $claims['personal_id'] ?? null;
        $u->name     = $claims['uname'] ?? '';   // ไม่มี name ใน payload ตัวอย่าง
        $u->email    = null;
        $u->roles    = [];
        $u->access_token = $jwt;
        $u->exp = isset($claims['exp']) ? (int)$claims['exp'] : null;
        $u->iat = isset($claims['iat']) ? (int)$claims['iat'] : null;

        if (is_array($profile)) {
            $u->profile = $profile;
        }

        return $u;
    }

    /** โทเค็นหมดอายุหรือยัง */
    public function isExpired(): bool
    {
        return $this->exp !== null && time() >= $this->exp;
    }

    /** เหลือเวลากี่วินาทีก่อนหมดอายุ (อาจติดลบถ้าหมดแล้ว) */
    public function secondsToExpiry(): ?int
    {
        return $this->exp !== null ? ($this->exp - time()) : null;
    }

    /** สร้างชื่อที่จะแสดงจาก profile (ถ้ามี) */
    public function getDisplayName(): string
    {
        $title = $this->profile['title_name'] ?? '';
        $fn    = $this->profile['first_name'] ?? '';
        $ln    = $this->profile['last_name'] ?? '';
        $full  = trim("$title $fn $ln");
        if ($full !== '') return $full;
        return $this->name ?: ($this->username ?? 'Guest');
    }

    /** แปลงเป็นอาร์เรย์ (เก็บลง session) */
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
            'profile'      => $this->profile,   // <-- รวมโปรไฟล์ไว้เสมอ
        ];
    }

    /** ตั้งค่าโปรไฟล์ให้เป็นอาร์เรย์เสมอ (กันพัง) */
    public function setProfile($profile): void
    {
        $this->profile = is_array($profile) ? $profile : [];
    }

    /* ───────── IdentityInterface ───────── */

    public static function findIdentity($id)
    {
        $data = Yii::$app->session->get('identity');
        if ($data && (string)$data['id'] === (string)$id) {
            $u = new self();
            foreach ($data as $k => $v) {
                // กันกรณี profile ถูกเก็บเป็นสตริงเผลอ ๆ
                if ($k === 'profile') {
                    $u->profile = is_array($v) ? $v : [];
                } else {
                    $u->{$k} = $v;
                }
            }
            return $u;
        }
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $data = Yii::$app->session->get('identity');
        if ($data && ($data['access_token'] ?? null) === $token) {
            $u = new self();
            foreach ($data as $k => $v) {
                if ($k === 'profile') {
                    $u->profile = is_array($v) ? $v : [];
                } else {
                    $u->{$k} = $v;
                }
            }
            return $u;
        }
        return null;
    }

    public function getId() { return $this->id; }
    public function getAuthKey() { return null; }
    public function validateAuthKey($authKey) { return true; }

    /* ───────── Helpers ───────── */

    private static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return [];
        $payload = $parts[1] . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
        $json = base64_decode(strtr($payload, '-_', '+/'));
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
}
