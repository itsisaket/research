<?php
namespace app\models;

use Yii;
use yii\base\Model;

class SignupForm extends Model
{
    public $username;
    public $password;
    public $password_confirm;
    public $u_name;
    public $u_sname;
    public $u_email;
    public $u_tel;

    public function rules()
    {
        return [
            [['username','password','password_confirm','u_name','u_sname','u_email'], 'required'],
            [['username'], 'trim'],
            [['username'], 'string', 'min'=>4, 'max'=>50],
            [['password'], 'string', 'min'=>8, 'max'=>72],
            ['password_confirm', 'compare', 'compareAttribute'=>'password', 'message'=>'รหัสผ่านไม่ตรงกัน'],
            [['u_name','u_sname'], 'string', 'max'=>100],
            [['u_email'], 'email'],
            [['u_email'], 'string', 'max'=>100],
            [['u_tel'], 'string', 'max'=>20],
            ['username', 'unique', 'targetClass'=>User::class, 'targetAttribute'=>'username', 'message'=>'ชื่อนี้ถูกใช้แล้ว'],
            ['u_email',  'unique', 'targetClass'=>User::class, 'targetAttribute'=>'u_email',  'message'=>'อีเมลนี้ถูกใช้แล้ว'],
        ];
    }

    public function signup(): ?User
    {
        if (!$this->validate()) return null;

        $u = new User();
        $u->username = $this->username;
        $u->u_name   = $this->u_name;
        $u->u_sname  = $this->u_sname;
        $u->u_email  = $this->u_email;
        $u->u_tel    = $this->u_tel ?? '';

        // default meta
        $u->office_id = 0; $u->province_id = 0; $u->district_id = 0; $u->tambon_id = 0;
        $u->u_type    = User::ROLE_USER;
        $u->u_status1 = 1;
        $u->u_status2 = User::STATUS_ACTIVE;

        $u->setPassword($this->password);
        $u->generateAuthKey();

        return $u->save() ? $u : null;
    }
}
