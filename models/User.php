<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * User (stateless-like) สำหรับระบบที่ใช้ JWT จาก HRM
 *
 * จุดเด่น:
 * - ไม่ผูกกับฐานข้อมูล (ไม่มี ActiveRecord)
 * - ใช้ session เก็บข้อมูล identity ที่ถอดมาจาก JWT
 * - ใช้ได้กับ flow ที่หน้าเว็บส่ง {token, profile} มาที่ /site/my-profile
 * - มี helper สำหรับ decode JWT แบบ base64url
 *
 * ปรับเพิ่ม: รองรับ mapping
 * - username = personal_id
 * - prefix   = title_id
 * - uname    = first_name
 * - luname   = last_name
 * - org_id   = manage_faculty_id
 */
class User implements IdentityInterface
{
    /** @var string|null personal_id หรือ uname ที่ใช้เป็น PK */
    public $id;

    /** @var string|null ชื่อผู้ใช้ที่ frontend เข้าใจ (ส่วนมากคือ uname/personal_id) */
    public $username;

    /** @var string|null ชื่อเต็มจาก payload (ถ้ามี) */
    public $name;

    /** @var string|null email จาก payload หรือจาก profile */
    public $email;

    /** @var array รายการบทบาทจาก payload (ถ้ามี) */
    public $roles = [];

    /** @var string|null JWT เต็ม ๆ เก็บไว้เผื่อยิงต่อ */
    public $access_token;

    /** @var int|null เวลาหมดอายุ (unix time) จาก JWT */
    public $exp;

    /** @var int|null เวลาออก token (unix time) จาก JWT */
    public $iat;

    /** @var array โปรไฟล์ดิบจาก /authen/profile (normalized แล้ว) */
    public $profile = [];

    /* ===== ฟิลด์ที่ให้ตรงกับ tb_user ===== */
    /** @var string|null คำนำหน้า (prefix = title_id) */
    public $prefix;

    /** @var string|null ชื่อจริง (uname = first_name) */
    public $uname;

    /** @var string|null นามสกุล (luname = last_name) */
    public $luname;

    /** @var int|string|null หน่วยงาน (org_id = manage_faculty_id) */
    public $org_id;

    /** @var string|null ตำแหน่ง/สายงาน จาก HRM */
    public $position;

    /**
     * key สำหรับเก็บใน session
     */
    const SESSION_KEY = '_identity_data';

    /* ============================================================
     *           IdentityInterface (จำเป็นต้องมี)
     * ============================================================ */

    /**
     * ดึง user จาก session ด้วย id
     */
    public static function findIdentity($id)
    {
        $data = Yii::$app->session->get(self::SESSION_KEY);
        if (!$data || !isset($data['id'])) {
            return null;
        }

        // id ไม่ตรง → ไม่ใช่คนนี้
        if ((string)$data['id'] !== (string)$id) {
            return null;
        }

        // ถ้าหมดอายุแล้วให้ลบทิ้งทันที
        if (!empty($data['exp']) && is_numeric($data['exp']) && (int)$data['exp'] < time()) {
            Yii::$app->session->remove(self::SESSION_KEY);
            return null;
        }

        return self::fromArray($data);
    }

    /**
     * เราไม่ได้ใช้ findIdentityByAccessToken ใน flow นี้
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    /**
     * คืนค่า PK (เราเก็บ personal_id/uname ไว้ใน $id อยู่แล้ว)
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * เราไม่ได้ใช้ authKey ในระบบนี้ → คืน null ไป
     */
    public function getAuthKey()
    {
        return null;
    }

    /**
     * ไม่ได้ใช้ authKey → ให้ผ่านไปเลย
     */
    public function validateAuthKey($authKey)
    {
        return true;
    }

    /* ============================================================
     *                       Factory
     * ============================================================ */

/**
 * สร้าง User จาก JWT + โปรไฟล์ แล้วเก็บลง session และ login เข้าระบบ Yii
 *
 * @param string $jwt
 * @param array|null $profile
 * @return static
 */
public static function fromToken(string $jwt, array $profile = null): self
{
    $claims  = self::decodeJwtPayload($jwt);
    $profile = self::normalizeProfile($profile ?? []);

    $u = new self();

    // ------- ดึงค่าหลักตาม mapping ที่กำหนด -------

    $personalId = $claims['personal_id'] ?? ($profile['personal_id'] ?? null);
    $firstName  = $claims['first_name']  ?? ($profile['first_name'] ?? null);
    $lastName   = $claims['last_name']   ?? ($profile['last_name'] ?? null);

    // id / username
    $u->id       = $personalId ?: $firstName;
    $u->username = $personalId ?: ($claims['uname'] ?? $firstName);

    // name (ชื่อเต็ม)
    $u->name = $claims['name']
        ?? trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

    // email
    $u->email = $claims['email'] ?? self::pickEmail($profile);

    // roles
    $u->roles = is_array($claims['roles'] ?? null) ? $claims['roles'] : [];

    // exp / iat
    $u->exp = $claims['exp'] ?? null;
    $u->iat = $claims['iat'] ?? null;

    // mapping เสริม
    $u->prefix = $claims['title_id']
        ?? $profile['title_id']
        ?? $profile['title_name']
        ?? null;

    $u->uname  = $firstName;
    $u->luname = $lastName;
    $u->org_id = $claims['manage_faculty_id']
        ?? $profile['manage_faculty_id']
        ?? null;

    // ⭐ เพิ่มตรงนี้
    $u->position = $claims['position']
        ?? $profile['position']
        ?? $profile['employee_type_name']
        ?? $profile['category_type_name']
        ?? null;

    // เก็บโปรไฟล์ดิบ
    $u->profile = $profile;

    // เก็บ JWT ไว้เผื่อใช้ต่อ
    $u->access_token = $jwt;

    // ✅ เก็บทุกอย่างลง session
    Yii::$app->session->set(self::SESSION_KEY, $u->toArray());

    // ✅ login เข้าระบบ Yii ด้วย object นี้
    try {
        Yii::$app->user->login($u, 60 * 60 * 8); // login 8 ชั่วโมง
        Yii::info('User login success: ' . $u->username, 'auth');
    } catch (\Throwable $e) {
        Yii::warning('User login failed: ' . $e->getMessage(), 'auth');
    }

    return $u;
}

    /**
     * สร้าง User จาก array ที่ดึงออกมาจาก session
     *
     * @param array $arr
     * @return static
     */
    public static function fromArray(array $arr): self
    {
        $u = new self();

        foreach ($arr as $k => $v) {
            if ($k === 'roles' && !is_array($v)) {
                $v = [];
            }
            if ($k === 'profile') {
                $v = self::normalizeProfile($v);
            }
            if (property_exists($u, $k)) {
                $u->$k = $v;
            }
        }

        // กันกรณี email ไม่มี แต่ profile มี
        if (empty($u->email) && !empty($u->profile)) {
            $u->email = self::pickEmail($u->profile);
        }

        return $u;
    }

    /**
     * แปลงเป็น array สำหรับเก็บลง session
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'username'     => $this->username,
            'name'         => $this->name,
            'email'        => $this->email ?: self::pickEmail($this->profile),
            'roles'        => array_values($this->roles ?: []),
            'access_token' => $this->access_token,
            'exp'          => $this->exp,
            'iat'          => $this->iat,
            'profile'      => $this->profile,

            // เก็บฟิลด์แมปไปด้วย
            'prefix'       => $this->prefix,
            'uname'        => $this->uname,
            'luname'       => $this->luname,
            'org_id'       => $this->org_id,
            'position'     => $this->position, 
        ];
    }

    /* ============================================================
     *                   JWT helpers
     * ============================================================ */

    /**
     * ถอด payload ของ JWT (base64url → json)
     *
     * @param string $jwt
     * @return array
     */
    public static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return [];
        }

        $payload = self::b64urlDecode($parts[1]);
        $json    = json_decode($payload, true);

        return is_array($json) ? $json : [];
    }

    /**
     * base64url decode
     */
    private static function b64urlDecode(string $str): string
    {
        $str = strtr($str, '-_', '+/');
        $pad = strlen($str) % 4;
        if ($pad) {
            $str .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode($str);

        return $bin === false ? '' : $bin;
    }

    /* ============================================================
     *                    Internal utils
     * ============================================================ */

    /**
     * รับได้ทั้งแบบ {profile:{...}} หรือ {...}
     * คืน array โปรไฟล์ด้านในเสมอ
     *
     * @param mixed $profile
     * @return array
     */
    private static function normalizeProfile($profile): array
    {
        if (!is_array($profile)) {
            return [];
        }

        // ถ้าเป็น {profile:{...}} ให้ดึงด้านในออกมา
        $p = isset($profile['profile']) && is_array($profile['profile'])
            ? $profile['profile']
            : $profile;

        // แปลงค่า null ที่ใช้แสดงผลให้เป็น '' จะได้ไม่ error เวลา echo
        $keys = [
            'title_name',
            'title_id',
            'first_name',
            'last_name',
            'email',
            'email_uni_google',
            'email_uni_microsoft',
            'img',
            'dept_name',
            'category_type_name',
            'employee_type_name',
            'academic_type_name',
            'personal_id',
            'manage_faculty_id',
        ];

        foreach ($keys as $k) {
            if (array_key_exists($k, $p) && $p[$k] === null) {
                $p[$k] = '';
            }
        }

        return $p;
    }

    /**
     * เลือก email ที่เหมาะสมที่สุดจากโปรไฟล์
     */
    private static function pickEmail(array $p): ?string
    {
        return $p['email']
            ?? $p['email_uni_google']
            ?? $p['email_uni_microsoft']
            ?? null;
    }

    /**
     * เช็กว่า user นี้หมดอายุตาม exp หรือยัง
     */
    public function isExpired(): bool
    {
        if (empty($this->exp)) {
            return false;
        }
        return (int)$this->exp < time();
    }

    /**
     * ลบ identity ออกจาก session (สำหรับใช้ตอน logout)
     */
    public static function removeFromSession(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY);
    }
}
