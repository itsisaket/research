<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use app\models\User;

/**
 * รับ login แบบ uname/pwd -> เรียก SSO -> ได้ JWT -> login Yii2
 */
class AuthController extends Controller
{
    public $enableCsrfValidation = true;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'password-login' => ['POST'],   // ฟอร์มส่งมา
                    'jwt-login'      => ['POST'],   // กรณีมี JWT ตรง ๆ
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['jwt-login'],
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
        ];
    }

    /** ฟอร์ม POST uname/pwd (x-www-form-urlencoded หรือ multipart) */
    public function actionPasswordLogin()
    {
        $uname = Yii::$app->request->post('uname');
        $pwd   = Yii::$app->request->post('pwd');

        if (!$uname || !$pwd) {
            Yii::$app->session->setFlash('error', 'โปรดกรอกชื่อผู้ใช้และรหัสผ่าน');
            return $this->redirect(['/site/login']);
        }

        /** @var \app\components\ApiAuthService $api */
        $api = Yii::$app->get('apiAuth');
        $token = $api->login($uname, $pwd);

        if (!$token) {
            Yii::$app->session->setFlash('error', 'เข้าสู่ระบบไม่สำเร็จ (uname/pwd ไม่ถูกต้อง หรือ SSO ล่ม)');
            return $this->redirect(['/site/login']);
        }

        $claims = User::decodeJwtPayload($token);
        if (!$claims || User::isExpired($claims)) {
            Yii::$app->session->setFlash('error', 'Token ไม่ถูกต้องหรือหมดอายุแล้ว');
            return $this->redirect(['/site/login']);
        }

        $profile = $api->fetchProfile($token); // optional
        $user = User::fromClaims($claims, $token, $profile);
        Yii::$app->user->login($user, 3600*8);
        $user->persistToSession();

        // ใส่ token ลงคุกกี้ชั่วคราว เพื่อให้ JS ย้ายไป localStorage
        Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name'     => 'hrm-sci-token',
            'value'    => $token,
            'httpOnly' => false, // ให้ JS อ่านได้
            'secure'   => true,
            'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
            'expire'   => time() + 300, // 5 นาที
        ]));

        return $this->redirect(['/site/index']);
    }

    /** เดิม: login ด้วย JWT โดยตรง (เช่นมาจาก localStorage) */
    public function actionJwtLogin()
    {
        $req = Yii::$app->request;
        $auth = $req->headers->get('Authorization', '');
        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) $token = trim($m[1]);
        if (!$token) {
            $arr = json_decode($req->getRawBody(), true);
            if (isset($arr['token'])) $token = $arr['token'];
        }
        if (!$token) { Yii::$app->response->statusCode = 400; return ['ok'=>false,'error'=>'TOKEN_MISSING']; }

        $claims = User::decodeJwtPayload($token);
        if (!$claims || User::isExpired($claims)) { Yii::$app->response->statusCode = 401; return ['ok'=>false,'error'=>'TOKEN_INVALID_OR_EXPIRED']; }

        /** @var \app\components\ApiAuthService $api */
        $api = Yii::$app->get('apiAuth');
        $profile = $api->fetchProfile($token); // optional

        $user = User::fromClaims($claims, $token, $profile);
        Yii::$app->user->login($user, 3600*8);
        $user->persistToSession();

        return ['ok'=>true, 'user'=>[
            'id'=>$user->id, 'username'=>$user->username, 'name'=>$user->name,
            'email'=>$user->email, 'roles'=>$user->roles, 'exp'=>$user->exp,
        ]];
    }
}
