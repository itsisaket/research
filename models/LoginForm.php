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
        if (!$this->validate()) return false;

        try {
            /** @var \app\components\ApiAuthService $auth */
            $auth = \Yii::$app->apiAuth;
            $token = $auth->login($this->username, $this->password);

            $user = \app\models\User::fromToken($token);

            // ถ้าหมดอายุแล้วไม่ให้เข้า
            if ($user->isExpired()) {
                $this->addError('username', 'โทเค็นหมดอายุแล้ว กรุณาลองใหม่อีกครั้ง');
                return false;
            }

            // เก็บลง session
            Yii::$app->session->set('identity', $user->toArray());

            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;
            return Yii::$app->user->login($user, $duration);

        } catch (\DomainException $e) {
            $this->addError('password', $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            \Yii::error($e->getMessage(), 'auth.api');
            $this->addError('username', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ยืนยันตัวตนได้');
            return false;
        }
    }
}
