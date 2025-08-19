<?php
namespace app\components;

use Yii;
use yii\base\Component;
use app\models\User;

/**
 * SSO Auto-login จากคุกกี้ hrm-sci-token (JWT RS256)
 */
class HrmSciSso extends Component
{
    public $cookieName = 'hrm-sci-token';
    /** วาง Public Key PEM ของฝั่ง authen (RS256) */
    public $publicKeyPem = ''; // TODO: ใส่คีย์จริง
    public $allowUnsafeDecode = false; // true ใช้ได้เฉพาะ DEV เท่านั้น!
    public $leeway = 60;
    public $expectedIss; // ตัวอย่าง: 'sci-sskru-authen'
    public $expectedAud; // ตัวอย่าง: 'ris-portal'

    /**
     * เรียกใน beforeRequest: ถ้าพบคุกกี้และ verify ผ่าน -> login
     */
    public function tryAutoLoginFromCookie(): bool
    {
        if (!Yii::$app->user->isGuest) {
            return false;
        }
        $jwt = $_COOKIE[$this->cookieName] ?? null; // bypass cookieValidation ของ Yii
        if (!$jwt || !is_string($jwt)) {
            return false;
        }

        $claims = $this->validateAndDecodeJwt($jwt);
        if ($claims === null) {
            return false;
        }

        $user = User::fromToken($jwt);
        // ดึงโปรไฟล์แนบให้ UI ใช้ (ไม่ทำให้ล้มถ้าพลาด)
        try {
            $profile = Yii::$app->apiAuth->getProfileByPersonalId((string)$user->id);
            if (is_array($profile)) {
                $user->setProfile($profile);
            }
        } catch (\Throwable $e) {
            Yii::warning('SSO profile fetch failed: '.$e->getMessage(), 'sso');
        }

        Yii::$app->session->set('identity', $user->toArray());
        return Yii::$app->user->login($user, 0);
    }

    /** คืน payload array ถ้าผ่าน, ไม่ผ่านคืน null */
    public function validateAndDecodeJwt(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        [$h64, $p64, $s64] = $parts;
        $header  = $this->b64urlJsonDecode($h64);
        $payload = $this->b64urlJsonDecode($p64);
        $sig     = $this->b64urlDecode($s64);

        if (!is_array($header) || !is_array($payload) || $sig === false) return null;
        if (($header['alg'] ?? '') !== 'RS256') return null;

        if ($this->publicKeyPem) {
            $data = $h64.'.'.$p64;
            if (!$this->verifyRs256($data, $sig, $this->publicKeyPem)) return null;
        } elseif (!$this->allowUnsafeDecode) {
            return null;
        }

        $now = time();
        if (isset($payload['exp']) && $now > ((int)$payload['exp'] + $this->leeway)) return null;
        if (isset($payload['nbf']) && $now + $this->leeway < (int)$payload['nbf']) return null;
        if (isset($payload['iat']) && $now + $this->leeway < (int)$payload['iat']) return null;
        if ($this->expectedIss && isset($payload['iss']) && $payload['iss'] !== $this->expectedIss) return null;
        if ($this->expectedAud && isset($payload['aud']) && $payload['aud'] !== $this->expectedAud) return null;

        return $payload;
    }

    private function verifyRs256(string $data, string $signature, string $publicKeyPem): bool
    {
        $pub = openssl_pkey_get_public($publicKeyPem);
        if ($pub === false) {
            Yii::warning('Invalid SSO public key', 'sso');
            return false;
        }
        $ok = openssl_verify($data, $signature, $pub, OPENSSL_ALGO_SHA256) === 1;
        openssl_free_key($pub);
        return $ok;
    }

    private function b64urlDecode(string $s)
    {
        $p = strlen($s) % 4;
        if ($p) $s .= str_repeat('=', 4 - $p);
        return base64_decode(strtr($s, '-_', '+/'));
    }
    private function b64urlJsonDecode(string $s): ?array
    {
        $bin = $this->b64urlDecode($s);
        $arr = json_decode($bin, true);
        return is_array($arr) ? $arr : null;
    }
}
