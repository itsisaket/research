<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use app\models\User;
use app\components\ApiAuthService;

class AuthController extends Controller
{
    // ปิด CSRF เฉพาะ action นี้ (เพราะเรียกแบบ JSON)
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['jwt-login' => ['POST']],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['jwt-login'],
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
        ];
    }

    /** POST /auth/jwt-login  (Authorization: Bearer <token>) หรือ JSON { "token": "..." } */
    public function actionJwtLogin()
    {
        $req = Yii::$app->request;
        $auth = $req->headers->get('Authorization', '');
        $token = null;

        if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
            $token = trim($m[1]);
        }
        if (!$token) {
            $json = $req->getRawBody();
            if ($json) {
                $arr = json_decode($json, true);
                if (isset($arr['token'])) $token = $arr['token'];
            }
        }
        if (!$token) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'TOKEN_MISSING'];
        }

        $claims = User::decodeJwtPayload($token);
        if (!$claims) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'error' => 'TOKEN_INVALID'];
        }

        // เช็คหมดอายุแบบเร็ว ๆ
        if (User::isExpired($claims)) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'error' => 'TOKEN_EXPIRED'];
        }

        // (แนะนำ) ตรวจสอบลายเซ็น RS256 กับ Public Key (ถ้ามี)
        // ข้ามขั้นตอนนี้ได้ถ้าจะใช้วิธีเรียก profile จาก SSO เป็นตัว validate
        // $verified = $this->verifyJwtRs256($token, Yii::$app->params['jwtPublicKey'] ?? null);

        // ดึงโปรไฟล์จาก SSO (ถือเป็นการ validate จริง)
        /** @var ApiAuthService $api */
        $api = Yii::$app->get('apiAuth');
        $profile = $api ? $api->fetchProfile($token) : null;

        // สร้าง Identity แล้ว login
        $user = User::fromClaims($claims, $token, $profile);
        $duration = 3600 * 8; // 8 ชั่วโมง (หรือ 0 = session-only)
        Yii::$app->user->login($user, $duration);
        $user->persistToSession();

        return [
            'ok' => true,
            'user' => [
                'id'       => $user->id,
                'username' => $user->username,
                'name'     => $user->name,
                'email'    => $user->email,
                'roles'    => $user->roles,
                'exp'      => $user->exp,
            ],
        ];
    }

    /** (ถ้าต้องการ) ตรวจลายเซ็น RS256 ด้วย Public Key */
    private function verifyJwtRs256(string $jwt, ?string $publicKey): bool
    {
        if (!$publicKey) return false;
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;

        [$h, $p, $s] = $parts;
        $data = $h.'.'.$p;
        $sig = strtr($s, '-_', '+/');
        $sig = base64_decode(str_pad($sig, strlen($sig) % 4 === 0 ? strlen($sig) : strlen($sig) + 4 - strlen($sig) % 4, '=', STR_PAD_RIGHT));

        $key = openssl_pkey_get_public($publicKey);
        if (!$key) return false;

        $ok = openssl_verify($data, $sig, $key, OPENSSL_ALGO_SHA256) === 1;
        openssl_free_key($key);
        return $ok;
    }
}
