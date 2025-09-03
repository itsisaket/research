<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use app\models\User;

/**
 * proxy โปรไฟล์: รับ token + personal_id จาก frontend
 * แล้วไปเรียก POST /authen/profile (ของ SSO) ให้
 */
class AuthController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['profile' => ['POST']],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only'  => ['profile'],
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
        ];
    }

    public function actionProfile()
    {
        $body = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $token = $body['token'] ?? null; // รับจาก body
        if (!$token) {
            // เผื่อส่งมาใน header Authorization ก็ได้
            $auth = Yii::$app->request->headers->get('Authorization', '');
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
                $token = trim($m[1]);
            }
        }

        if (!$token) {
            Yii::$app->response->statusCode = 400;
            return ['ok'=>false,'error'=>'TOKEN_MISSING'];
        }

        // ถ้าผู้ใช้ไม่ส่ง personal_id มา เราถอดจาก payload ให้
        $personalId = $body['personal_id'] ?? (User::decodeJwtPayload($token)['personal_id'] ?? null);
        if (!$personalId) {
            Yii::$app->response->statusCode = 400;
            return ['ok'=>false,'error'=>'PERSONAL_ID_MISSING'];
        }

        /** @var \app\components\ApiAuthService $api */
        $api = Yii::$app->get('apiAuth');
        $profile = $api->fetchProfileWithPost($token, $personalId);

        if (!$profile) {
            Yii::$app->response->statusCode = 401;
            return ['ok'=>false,'error'=>'TOKEN_INVALID_OR_EXPIRED_OR_NO_PROFILE'];
        }

        return ['ok'=>true, 'profile'=>$profile];
    }
}
