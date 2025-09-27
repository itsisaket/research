<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    // STATUS
    const STATUS_DELETED    = 0;
    const STATUS_NOT_ACTIVE = 5;
    const STATUS_ACTIVE     = 10;

    // ROLES
    const ROLE_USER      = 1;
    const ROLE_MODERATOR = 2;
    const ROLE_ADMIN     = 4;

    public static function tableName(){ return 'tb_user'; }

    // IdentityInterface
    public static function findIdentity($id){
        return static::findOne(['u_id' => $id, 'u_status2' => self::STATUS_ACTIVE]);
    }
    public static function findIdentityByAccessToken($token, $type = null){ return null; }

    public static function findByUsername($username){
        return static::findOne(['username' => $username, 'u_status2' => self::STATUS_ACTIVE]);
    }

    public function getId(){ return $this->u_id; }
    public function getAuthKey(){ return $this->auth_key; }
    public function validateAuthKey($authKey){ return $this->auth_key === $authKey; }

    // Password: bcrypt
    public function setPassword(string $password): void {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }
    public function validatePassword(string $password): bool {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    // Tokens
    public function generateAuthKey(): void {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    // Helpers
    public function isActive(): bool { return (int)$this->u_status2 === self::STATUS_ACTIVE; }
    public function isAdmin(): bool { return (int)$this->u_type === self::ROLE_ADMIN; }

    // timestamps
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) return false;
        $now = time();
        if ($insert) $this->created_at = $now;
        $this->updated_at = $now;
        return true;
    }
}
