<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\components\ApiAuthService;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user; // User identity

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'ชื่อผู้ใช้ / เลขบัตร',
            'password' => 'รหัสผ่าน',
            'rememberMe' => 'จดจำฉันไว้ในระบบ',
        ];
    }

    public function login(): bool
    {
        try {
            // 1) เรียก authen/login เพื่อรับ JWT
            $token = Yii::$app->apiAuth->login($this->username, $this->password);

            // 2) สร้าง User จาก JWT
            $user = User::fromToken($token);

            // 3) ดึงโปรไฟล์แนบให้ UI ใช้
            try {
                $profile = Yii::$app->apiAuth->getProfileByPersonalId((string)$user->id);
                $user->setProfile($profile);
            } catch (\Throwable $e) {
                Yii::warning('Login profile fetch failed: '.$e->getMessage(), 'auth.profile');
            }

            // 4) เก็บลง session + login
            Yii::$app->session->set('identity', $user->toArray());
            $duration = $this->rememberMe ? 3600*24*30 : 0;
            $ok = Yii::$app->user->login($user, $duration);

            // 5) ตั้งคุกกี้ SSO (ปรับโดเมนให้ตรงระบบจริงของคุณ)
            // DEV บน localhost: ใส่ domain=null, secure=false ได้
            Yii::$app->response->cookies->add(new Cookie([
                'name'     => 'hrm-sci-token',
                'value'    => $token,
                'domain'   => '.sci-sskru.com',        // TODO: ปรับให้ตรงโดเมนแม่ร่วม (หรือ null)
                'path'     => '/',
                'httpOnly' => true,
                'secure'   => true,                   // ใช้ HTTPS จริง
                'sameSite' => Cookie::SAME_SITE_LAX,  // หรือ SAME_SITE_NONE + Secure ถ้าจำเป็น
                'expire'   => $user->exp ?? (time()+7*24*3600),
            ]));

            return $ok;
        } catch (\Throwable $e) {
            $this->addError('password', 'เข้าสู่ระบบไม่สำเร็จ: '.$e->getMessage());
            return false;
        }
    }
}
