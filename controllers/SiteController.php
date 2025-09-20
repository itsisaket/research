<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;

class SiteController extends Controller
{
    /** ปรับค่ามาตรฐานได้ที่นี่ */
    private const SESSION_DURATION = 60 * 60 * 24 * 14; // 14 วัน
    private const CLOCK_SKEW       = 120;               // ยอม clock-skew 120s
    private const MAX_BODY_BYTES   = 1048576;           // 1MB

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // ระบุชัดว่า index เข้าได้เสมอ, login/my-profile guest+user, logout ต้องเป็น user
                'only'  => ['index', 'login', 'my-profile', 'logout'],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['login', 'my-profile'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'my-profile' => ['POST'],
                    'logout'     => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $isGuest = Yii::$app->user->isGuest;
        $u       = $isGuest ? null : Yii::$app->user->identity; // \app\models\User|null
        return $this->render('index', compact('isGuest','u'));
    }

    public function actionLogin()
    {
        // หน้า login ของคุณเป็นแบบ “เงียบ” และ redirect อัตโนมัติ (ตามไฟล์ view ล่าสุด)
        return $this->render('login');
    }

    /**
     * POST /site/my-profile
     * Body JSON: { token: string, profile?: object }
     * หมายเหตุ:
     *   - จะพยายามยืนยันโปรไฟล์แบบ Server-to-Server (ถ้ามี yii\httpclient) เพื่อความครบถ้วนของข้อมูล UI
     *   - ถ้าไม่มีไลบรารี httpclient จะ fallback ใช้ profile จาก client (ถ้าส่งมา)
     */
    public function actionMyProfile(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // ---- Safeguards เบื้องต้น ----
        $raw = Yii::$app->request->getRawBody();
        if ($raw === '' || $raw === null) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'empty body'];
        }
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            Yii::$app->response->statusCode = 413;
            return ['ok' => false, 'error' => 'payload too large'];
        }
        $body = json_decode($raw, true);
        if (!is_array($body)) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'invalid json'];
        }

        $token   = (string)($body['token'] ?? '');
        $profile = is_array($body['profile'] ?? null) ? $body['profile'] : [];

        if ($token === '') {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'missing token'];
        }

        // ---- ตรวจ JWT claims/exp/personal_id ----
        $claims = User::decodeJwtPayload($token);
        if (empty($claims)) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'error' => 'invalid token payload'];
        }

        $now        = time();
        $exp        = isset($claims['exp']) ? (int)$claims['exp'] : null;
        $personalId = $claims['personal_id'] ?? $claims['uname'] ?? null;

        if ($exp && ($exp + self::CLOCK_SKEW) < $now) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'error' => 'token expired'];
        }
        if (empty($personalId)) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'token missing personal_id'];
        }

        // ถ้ามี profile มาจาก client และมี personal_id/ uname ให้ต้องตรงกับ token
        $pIdFromProfile = $profile['personal_id'] ?? $profile['uname'] ?? null;
        if ($pIdFromProfile && (string)$pIdFromProfile !== (string)$personalId) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'profile/payload personal_id mismatch'];
        }

        // ---- ยืนยันโปรไฟล์จากต้นทางแบบ Server-to-Server (ถ้ามีไลบรารี httpclient) ----
        // เปิดอัตโนมัติถ้า class มีอยู่; ไม่งั้น fallback ใช้ profile จาก client
        if (class_exists(\yii\httpclient\Client::class)) {
            try {
                /** @var \yii\httpclient\Client $client */
                $client = new \yii\httpclient\Client(['transport' => 'yii\httpclient\CurlTransport']);
                $resp = $client->createRequest()
                    ->setMethod('POST')
                    ->setUrl('https://sci-sskru.com/authen/profile')
                    ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
                    ->addHeaders(['Authorization' => 'Bearer '.$token])
                    ->setData(['personal_id' => $personalId])
                    ->send();

                if (!$resp->isOk) {
                    Yii::$app->response->statusCode = 401;
                    return ['ok' => false, 'error' => 'cannot verify profile'];
                }
                $profile = is_array($resp->data['profile'] ?? null)
                    ? $resp->data['profile']
                    : (is_array($resp->data ?? null) ? $resp->data : []);
            } catch (\Throwable $e) {
                // ถ้าดึงไม่สำเร็จ ให้ใช้ profile จาก client ตามที่ส่งมาแทน
            }
        }

        // ---- สร้าง identity & login (regenerate session id ก่อนและหลัง) ----
        $identity = User::fromToken($token, $profile);

        if (Yii::$app->session->isActive) {
            // regenerate ก่อน login
            Yii::$app->session->regenerateID(true);
        }

        Yii::$app->user->login($identity, self::SESSION_DURATION);

        if (Yii::$app->session->isActive) {
            // defense-in-depth: regenerate อีกครั้งหลัง login
            Yii::$app->session->regenerateID(true);
        }

        // ปรับ cache header สำหรับ endpoint นี้ (ไม่ให้ cache)
        $res = Yii::$app->response;
        $res->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $res->headers->set('Pragma', 'no-cache');

        return ['ok' => true, 'id' => $identity->id];
    }

    public function actionLogout()
    {
        // ล็อกเอาต์ + ทำลาย session + หมุน CSRF
        Yii::$app->user->logout(true);
        if (Yii::$app->session->isActive) {
            Yii::$app->session->destroy();
            Yii::$app->session->open();
            Yii::$app->session->regenerateID(true);
        }
        Yii::$app->request->getCsrfToken(true);

        return $this->goHome(); // ไป /site/index
    }

    public function actionContact()
    {
        return $this->render('contact');
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
