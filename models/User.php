<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

class User implements IdentityInterface
{
    public $id; public $username; public $name; public $email;
    public $roles = []; public $profile = []; public $access_token;

    public static function findIdentity($id){
        $data = Yii::$app->session->get('identity');
        return ($data && ($data['id'] ?? null) == $id) ? self::fromArray($data) : null;
    }
    public static function findIdentityByAccessToken($token, $type = null){ return null; }
    public function getId(){ return $this->id; }
    public function getAuthKey(){ return null; }
    public function validateAuthKey($authKey){ return true; }

    public static function fromArray(array $a): self {
        $u = new self(); foreach ($a as $k=>$v) { $u->$k = $v; } return $u;
    }

    public static function fromToken(string $jwt, array $apiResp): self {
        $p = $apiResp['profile'] ?? $apiResp;
        $pid   = $p['personal_id'] ?? null;
        $uname = $p['username'] ?? $pid;
        $name  = trim(($p['title_name'] ?? $p['title'] ?? '').' '.($p['first_name'] ?? '').' '.($p['last_name'] ?? ''));
        $email = $p['email'] ?? null;

        $u = new self();
        $u->id = $pid; $u->username = $uname; $u->name = $name ?: $uname;
        $u->email = $email; $u->roles = [ $p['role'] ?? 'user' ];
        $u->profile = $p; $u->access_token = $jwt;
        return $u;
    }
}
