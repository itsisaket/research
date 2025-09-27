<?php
namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = null;

    public function rules(){
        return [
            [['username','password'], 'required'],
            ['rememberMe','boolean'],
            ['password','validatePassword'],
        ];
    }

    public function validatePassword($attribute){
        if ($this->hasErrors()) return;
        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }
    }

    public function login()
    {
        if ($this->validate()) {
            // ให้จำการล็อกอิน 1 วัน (ไม่ใช่ 30 วันแล้ว)
            return Yii::$app->user->login($this->getUser(), 3600 * 24);
        }
        return false;
    }
    protected function getUser(){
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }
}
