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
    $claims = \app\models\User::decodeJwtPayload($token);
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

    // (ทางเลือก) ตรวจ iss/aud ถ้ามี
    $trustedIssuers = Yii::$app->params['trustedIssuers'] ?? ['https://sci-sskru.com'];
    if (!empty($claims['iss']) && !in_array($claims['iss'], $trustedIssuers, true)) {
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'untrusted issuer'];
    }

    // ถ้ามี profile จาก client และมี personal_id/ uname ให้ต้องตรงกับ token
    $pIdFromProfile = $profile['personal_id'] ?? $profile['uname'] ?? null;
    if ($pIdFromProfile && (string)$pIdFromProfile !== (string)$personalId) {
        Yii::$app->response->statusCode = 400;
        return ['ok' => false, 'error' => 'profile/payload personal_id mismatch'];
    }

    // ---- ยืนยันโปรไฟล์จากต้นทางแบบ Server-to-Server (ถ้ามีไลบรารี httpclient) ----
    $s2sOk = false;
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

            if ($resp->isOk) {
                $profile = is_array($resp->data['profile'] ?? null)
                    ? $resp->data['profile']
                    : (is_array($resp->data ?? null) ? $resp->data : []);
                $s2sOk = true;
            } else {
                Yii::warning(['my-profile s2s not ok', 'status' => $resp->statusCode, 'body' => $resp->content], __METHOD__);
            }
        } catch (\Throwable $e) {
            Yii::warning(['my-profile s2s error' => $e->getMessage()], __METHOD__);
        }
    }

    // ✅ Fallback: ถ้า S2S ไม่สำเร็จและ client ไม่ได้ส่ง profile มา
    //    สร้างโปรไฟล์ขั้นต่ำจาก JWT claims เพื่อให้ _navbar แสดงชื่อ/เมล/รูปได้
    if (!$s2sOk && empty($profile)) {
        $profile = [
            'personal_id'         => (string)$personalId,
            'uname'               => (string)($claims['uname'] ?? ''),
            'title_name'          => (string)($claims['title_name'] ?? ''),
            'first_name'          => (string)($claims['first_name'] ?? ''),
            'last_name'           => (string)($claims['last_name'] ?? ''),
            'name'                => (string)($claims['name'] ?? ''),
            'email'               => (string)($claims['email'] ?? ''),
            'email_uni_google'    => (string)($claims['email_uni_google'] ?? ''),
            'email_uni_microsoft' => (string)($claims['email_uni_microsoft'] ?? ''),
            'img'                 => (string)($claims['img'] ?? ''),
            'dept_name'           => (string)($claims['dept_name'] ?? ''),
            'category_type_name'  => (string)($claims['category_type_name'] ?? ''),
            'employee_type_name'  => (string)($claims['employee_type_name'] ?? ''),
            'academic_type_name'  => (string)($claims['academic_type_name'] ?? ''),
            'updated_at'          => (string)($claims['updated_at'] ?? ''), // เผื่อ cache-busting รูป
        ];
    }

    // ---- สร้าง identity & login (regenerate session id ก่อนและหลัง) ----
    $identity = \app\models\User::fromToken($token, $profile);

    if (!$identity->id) { // กันเคสผิดสเปค (ไม่ควรเกิดเพราะ personal_id ตรวจแล้ว)
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'unable to build identity'];
    }

    if (Yii::$app->session->isActive) {
        Yii::$app->session->regenerateID(true);  // ก่อน login
    }

    if (!Yii::$app->user->login($identity, self::SESSION_DURATION)) {
        Yii::$app->response->statusCode = 500;
        return ['ok' => false, 'error' => 'login failed'];
    }

    if (Yii::$app->session->isActive) {
        Yii::$app->session->regenerateID(true);  // หลัง login
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
