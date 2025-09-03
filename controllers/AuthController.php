<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use app\models\User;

class AuthController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(){
        return [
          'verbs'=>['class'=>VerbFilter::class,'actions'=>['jwt-login'=>['POST'],'callback'=>['GET']]],
          'contentNegotiator'=>['class'=>ContentNegotiator::class,'only'=>['jwt-login'], 'formats'=>['application/json'=>Response::FORMAT_JSON]],
        ];
    }

    /** POST /auth/jwt-login  {token}  */
    public function actionJwtLogin(){
        $req=Yii::$app->request; $token=null;
        if(preg_match('/Bearer\s+(.*)$/i',$req->headers->get('Authorization',''),$m)) $token=trim($m[1]);
        if(!$token){ $arr=json_decode($req->getRawBody(),true)?:[]; $token=$arr['token']??null; }
        if(!$token){ Yii::$app->response->statusCode=400; return ['ok'=>false,'error'=>'TOKEN_MISSING']; }

        $claims=User::decodeJwtPayload($token);
        if(!$claims){ Yii::$app->response->statusCode=401; return ['ok'=>false,'error'=>'TOKEN_INVALID']; }
        if(User::isExpired($claims)){ Yii::$app->response->statusCode=401; return ['ok'=>false,'error'=>'TOKEN_EXPIRED']; }

        $pid=(string)($claims['personal_id']??$claims['uname']??'');
        $profile = $pid!=='' ? Yii::$app->apiAuth->fetchProfileWithPost($token,$pid) : null;

        $user=User::fromClaims($claims,$token,$profile??[]);
        Yii::$app->user->login($user, 3600*8);
        $user->persistToSession();

        return ['ok'=>true,'user'=>['id'=>$user->id,'username'=>$user->username,'name'=>$user->name,'email'=>$user->email,'exp'=>$user->exp]];
    }

    /** GET /auth/callback?token=...   (มาจาก SSO แล้ว) */
    public function actionCallback($token=null){
        if(!$token) return $this->renderContent('<script>location.href="'.\yii\helpers\Url::to(['/site/login'],true).'";</script>');
        // เก็บ token ลง localStorage ทางฝั่ง client แล้วส่งไปสร้าง session
        $jwtLoginUrl = \yii\helpers\Url::to(['/auth/jwt-login'], true);
        $home        = \yii\helpers\Url::to(['/site/index'], true);
        $escToken    = \yii\helpers\Html::encode($token);
        return $this->renderContent(<<<HTML
<!doctype html><meta charset="utf-8">
<script>
  (function(){
    const t = "$escToken";
    try { localStorage.setItem('hrm-sci-token', t); } catch(e){}
    fetch("$jwtLoginUrl", {method:"POST", headers:{'Content-Type':'application/json','Authorization':'Bearer '+t}, body:JSON.stringify({token:t})})
      .then(r=>r.ok?r.json():Promise.reject())
      .then(d=>{ if(d&&d.ok){ location.replace("$home"); } else { throw new Error(); } })
      .catch(()=>{ localStorage.removeItem('hrm-sci-token'); location.replace("$home"); });
  })();
</script>
HTML);
    }
}
