<?php
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

class User implements IdentityInterface
{
    public $id; public $username; public $name; public $email;
    public $roles = []; public $access_token; public $exp; public $iat;
    public $profile = [];

    public static function findIdentity($id) {
        $u = Yii::$app->session->get('__jwt_user__');
        return ($u && (string)$u['id']===(string)$id) ? self::fromArray($u) : null;
    }
    public static function findIdentityByAccessToken($token, $type=null){ return null; }
    public function getId(){ return $this->id; }
    public function getAuthKey(){ return null; }
    public function validateAuthKey($authKey){ return true; }

    public static function fromArray(array $a): self { $u=new self; foreach($a as $k=>$v)$u->$k=$v;
        if(!is_array($u->profile))$u->profile=[]; if(!is_array($u->roles))$u->roles=[]; return $u; }

    public static function fromClaims(array $c, string $jwt, array $profile=null): self {
        $u=new self();
        $u->id=$c['personal_id']??$c['uname']??null;
        $u->username=$c['uname']??$c['personal_id']??null;
        $u->name=self::buildDisplayName($profile,$c);
        $u->email=$profile['email']??($c['email']??null);
        $u->roles=is_array($c['roles']??null)?$c['roles']:[];
        $u->access_token=$jwt; $u->iat=(int)($c['iat']??0); $u->exp=(int)($c['exp']??0);
        $u->profile=is_array($profile)?$profile:[]; return $u;
    }
    private static function buildDisplayName(?array $p,array $c):string{
        $p=is_array($p)?$p:[]; $parts=array_filter([$p['title_name']??null,$p['first_name']??null,$p['last_name']??null]);
        if($parts) return trim(implode(' ',$parts));
        return (string)($c['name']??$c['uname']??$c['personal_id']??'');
    }

    public static function decodeJwtPayload(string $jwt): array {
        $parts=explode('.',$jwt); if(count($parts)<2)return [];
        $payload=$parts[1]; $payload.=str_repeat('=',(4-strlen($payload)%4)%4);
        $json=base64_decode(strtr($payload,'-_','+/')); $a=json_decode($json,true); return is_array($a)?$a:[];
    }
    public static function isExpired(array $claims): bool { return !isset($claims['exp']) || time()>=(int)$claims['exp']; }

    public function persistToSession(): void {
        Yii::$app->session->set('__jwt_user__', [
            'id'=>$this->id,'username'=>$this->username,'name'=>$this->name,'email'=>$this->email,
            'roles'=>$this->roles,'access_token'=>$this->access_token,'iat'=>$this->iat,'exp'=>$this->exp,
            'profile'=>$this->profile,
        ]);
    }
}
