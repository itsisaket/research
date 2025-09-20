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
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // อนุญาตเฉพาะ 3 action นี้ให้ผ่านตัวกรอง (index เข้าได้เสมอ)
                'only'  => ['login', 'my-profile', 'logout'],
                'rules' => [
                    // login / my-profile อนุญาตทั้ง guest และ user (ไว้ refresh session ได้)
                    [
                        'actions' => ['login', 'my-profile'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    // logout ต้องเป็นผู้ใช้ที่ล็อกอินแล้วเท่านั้น
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
        return $this->render('login');
    }

    /**
     * POST /site/my-profile
     * Body JSON: { token: string, profile?: object }
     * หมายเหตุ: ถ้าอยากเข้มขึ้น ให้ไม่เชื่อ "profile" จาก client และดึงจากต้นทางด้วย server-to-server (ดูบล็อกคอมเมนต์ด้านล่าง)
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
        // กัน payload ใหญ่ผิดปกติ (> 1MB)
        if (strlen($raw) > 1024 * 1024) {
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

        $leeway     = 120; // ยอม clock-skew 120s
        $now        = time();
        $exp        = isset($claims['exp']) ? (int)$claims['exp'] : null;
        $personalId = $claims['personal_id'] ?? $claims['uname'] ?? null;

        if ($exp && ($exp + $leeway) < $now) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'error' => 'token expired'];
        }
        if (empty($personalId)) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'token missing personal_id'];
        }

        // ถ้า client ส่ง profile มาด้วย และมี personal_id/ uname ให้ต้องตรงกับใน token
        $pIdFromProfile = $profile['personal_id'] ?? $profile['uname'] ?? null;
        if ($pIdFromProfile && (string)$pIdFromProfile !== (string)$personalId) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'profile/payload personal_id mismatch'];
        }

        /*  ===== (ทางเลือกแนะนำ) ยืนยันโปรไฟล์จากต้นทางแบบ server-to-server =====
         *  เปิดคอมเมนต์บล็อกด้านล่างนี้หากต้องการ "ไม่เชื่อ profile จาก client"
         *  ต้องเปิดใช้ yii\httpclient ในโครงการก่อน
         *
         *  use yii\httpclient\Client;
         *  $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
         *  $resp = $client->createRequest()
         *      ->setMethod('POST')
         *      ->setUrl('https://sci-sskru.com/authen/profile')
         *      ->setFormat(Client::FORMAT_JSON)
         *      ->addHeaders(['Authorization' => 'Bearer '.$token])
         *      ->setData(['personal_id' => $personalId])
         *      ->send();
         *  if (!$resp->isOk) {
         *      Yii::$app->response->statusCode = 401;
         *      return ['ok' => false, 'error' => 'cannot verify profile'];
         *  }
         *  $profile = is_array($resp->data['profile'] ?? null) ? $resp->data['profile'] : (is_array($resp->data ?? null) ? $resp->data : []);
         */

        // ---- สร้าง identity และ login ----
        $identity = User::fromToken($token, $profile);

        // regenerate session id กัน session fixation
        if (Yii::$app->session->isActive) {
            Yii::$app->session->regenerateID(true);
        }

        // อายุ session 14 วัน
        Yii::$app->user->login($identity, 60 * 60 * 24 * 14);

        return ['ok' => true, 'id' => $identity->id];
    }

    public function actionLogout()
    {
        Yii::$app->user->logout(true);   // เคลียร์ identity + ทำลาย session
        Yii::$app->session->destroy();   // กัน edge case
        Yii::$app->request->getCsrfToken(true); // หมุน CSRF ใหม่
        return $this->goHome();
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
