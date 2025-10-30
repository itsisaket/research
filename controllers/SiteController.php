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
                'rules' => [
                    [
                        // หน้า public เข้าได้หมด
                        'actions' => ['index', 'login', 'error', 'about'],
                        'allow'   => true,
                    ],
                    [
                        'actions' => ['logout', 'my-profile'],
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
        // ถ้าล็อกอินอยู่แล้วก็กลับหน้าแรก
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        // พยายาม SSO จาก cookie
        try {
            Yii::$app->sso->tryAutoLoginFromCookie();
            if (!Yii::$app->user->isGuest) {
                return $this->goHome();
            }
        } catch (\Throwable $e) {
            Yii::warning('SSO auto-login failed: ' . $e->getMessage(), 'sso');
        }

        return $this->render('login');
    }

    /**
     * POST /site/my-profile
     * Body JSON: { token: string, profile?: object }
     * - ยืนยันโปรไฟล์แบบ Server-to-Server ได้ (ถ้ามี yii\httpclient)
     * - ถ้า S2S ไม่สำเร็จ จะ fallback ใช้ profile จาก client หรือ claims
     * - บังคับให้ username = personal_id เสมอ (ไม่สนใจ uname)
     */
public function actionMyProfile(): array
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    // ---- Safeguards ----
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

    // ---- Decode & validate JWT ----
    $claims = \app\models\User::decodeJwtPayload($token);
    if (empty($claims)) {
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'invalid token payload'];
    }

    $now        = time();
    $exp        = isset($claims['exp']) ? (int)$claims['exp'] : null;
    $personalId = $claims['personal_id'] ?? null; // ใช้ personal_id เท่านั้น

    if ($exp && ($exp + self::CLOCK_SKEW) < $now) {
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'token expired'];
    }
    if (empty($personalId)) {
        Yii::$app->response->statusCode = 400;
        return ['ok' => false, 'error' => 'token missing personal_id'];
    }

    // (optional) issuer check
    $trustedIssuers = Yii::$app->params['trustedIssuers'] ?? ['https://sci-sskru.com'];
    if (!empty($claims['iss']) && !in_array($claims['iss'], $trustedIssuers, true)) {
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'untrusted issuer'];
    }

    // profile consistency (ถ้าส่งจาก client) → เทียบกับ personal_id เท่านั้น
    $pIdFromProfile = $profile['personal_id'] ?? $profile['username'] ?? null;
    if ($pIdFromProfile && (string)$pIdFromProfile !== (string)$personalId) {
        Yii::$app->response->statusCode = 400;
        return ['ok' => false, 'error' => 'profile/payload personal_id mismatch'];
    }

    // ---- S2S profile verification (ถ้ามี httpclient) ----
    $s2sOk = false;
    if (class_exists(\yii\httpclient\Client::class)) {
        try {
            $client = new \yii\httpclient\Client(['transport' => 'yii\httpclient\CurlTransport']);
            $resp = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('https://sci-sskru.com/authen/profile')
                ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
                ->addHeaders(['Authorization' => 'Bearer '.$token])
                ->setOptions([
                    CURLOPT_TIMEOUT => 8,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ])
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

    // บังคับ username = personal_id เสมอ ไม่ว่ามาจาก S2S หรือ client
    $profile['username'] = (string)$personalId;

    // ---- Fallback profile from claims (ถ้าไม่มี S2S/Client profile) ----
    if (!$s2sOk && empty($profile)) {
        $profile = [
            'username'            => (string)$personalId,
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
            'updated_at'          => (string)($claims['updated_at'] ?? ''),
        ];
    }

    // ---- Build identity & login ----
    $identity = \app\models\User::fromToken($token, $profile);
    if (!$identity->id) {
        Yii::$app->response->statusCode = 401;
        return ['ok' => false, 'error' => 'unable to build identity'];
    }

    // ล็อกอินก่อน แล้วค่อยเปลี่ยน session id
    if (!Yii::$app->user->login($identity, self::SESSION_DURATION)) {
        Yii::$app->response->statusCode = 500;
        return ['ok' => false, 'error' => 'login failed'];
    }
    if (Yii::$app->session->isActive) {
        // เปลี่ยน id แต่ไม่ลบ data → ไม่ทำให้ _identity หาย
        Yii::$app->session->regenerateID(false);
    }

    // ---- No-cache headers ----
    $res = Yii::$app->response;
    $res->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $res->headers->set('Pragma', 'no-cache');

    // ---------- Sync/Upsert tb_user (username = personal_id เสมอ) ----------
    try {
        $username = (string)$personalId;
        if ($username === '') {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false, 'error' => 'username missing in token'];
        }

        // '' -> null helper
        $nz = static function($v) { $v = trim((string)$v); return $v === '' ? null : $v; };

        $first   = (string)($profile['first_name'] ?? $claims['first_name'] ?? $claims['name'] ?? '');
        $last    = (string)($profile['last_name']  ?? $claims['last_name']  ?? '');
        $email   = $nz($profile['email'] ?? $claims['email'] ?? '');
        $tel     = $nz($profile['tel'] ?? '');
        $orgId   = (int)($profile['dept_id'] ?? $profile['org_id'] ?? 0);

        $titleText = (string)($profile['title_name'] ?? '');
        $prefixMap = ['นาย' => 1, 'นาง' => 2, 'นางสาว' => 3];
        $prefix    = $prefixMap[$titleText] ?? null;

        Yii::$app->db->createCommand()->upsert('tb_user', [
            'username' => $username,
            'uname'    => $first !== '' ? $first : $username,
            'luname'   => $last,
            'email'    => $email,
            'tel'      => $tel,
            'prefix'   => $prefix,
            'org_id'   => $orgId,
            'position' => 10,
            'authKey'  => Yii::$app->security->generateRandomString(32),
            'dayup'    => new \yii\db\Expression('NOW()'),
        ], [
            'uname'  => $first !== '' ? $first : new \yii\db\Expression('uname'),
            'luname' => $last  !== '' ? $last  : new \yii\db\Expression('luname'),
            'email'  => $email !== null ? $email : new \yii\db\Expression('email'),
            'tel'    => $tel   !== null ? $tel   : new \yii\db\Expression('tel'),
            'prefix' => $prefix !== null ? $prefix : new \yii\db\Expression('prefix'),
            'org_id' => $orgId > 0 ? $orgId : new \yii\db\Expression('org_id'),
            'dayup'  => new \yii\db\Expression('NOW()'),
        ])->execute();

    } catch (\Throwable $e) {
        Yii::error(['tb_user upsert exception' => $e->getMessage()], __METHOD__);
        // ไม่ hard-fail
    }

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
