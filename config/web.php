<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'Lasc SSKRU',
    'name' => 'ศูนย์วิจัย LASC มหาวิทยาลัยราชภัฏศรีสะเกษ',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@web' => 'research/web/',  // ตั้งค่าให้ชี้ไปที่โฟลเดอร์ web
    ],
    'layout' => 'berry',
    'components' => [
        'jwt' => [
            'class' => \sizeg\jwt\Jwt::class,
            'key' => '4f1g23a12aa', // ตั้งค่า secret key ที่ใช้ในการเข้ารหัส
            'jwtValidationData' => \sizeg\jwt\JwtValidationData::class,
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '1smD3uuUUKbmNvh_mUhJnUW3qMAI-IUC',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
            ],
        'apiClient' => [
            'class' => \yii\httpclient\Client::class,
            'baseUrl' => 'https://sci-sskru.com', // TODO: ปรับให้ตรง, แล้วเรียก setUrl('/authen/...') ใน service
            'transport' => \yii\httpclient\CurlTransport::class,
            'formatters' => [
                \yii\httpclient\Client::FORMAT_JSON => \yii\httpclient\JsonFormatter::class,
            ],
            'on beforeSend' => function ($e) {
                $id = Yii::$app->user->identity;
                if ($id && !empty($id->access_token) && !$e->request->getHeaders()->has('Authorization')) {
                $e->request->addHeaders(['Authorization' => 'Bearer '.$id->access_token]);
                }
            },
        ],
        'apiAuth' => [
            'class' => app\components\ApiAuthService::class,
            ],
        'sso' => [
            'class' => app\components\HrmSciSso::class,
            'cookieName' => 'hrm-sci-token',
            'publicKeyPem' => '',          // TODO: วาง RS256 public key PEM (โปรดักชันต้องใส่)
            'allowUnsafeDecode' => false,  // DEV เท่านั้นที่ควรเป็น true
            'leeway' => 60,
            'expectedIss' => null,
            'expectedAud' => null,
        ],
            'cache' => ['class' => \yii\caching\FileCache::class],
        // Auto SSO: guest เท่านั้น
        'on beforeRequest' => function () {
            if (Yii::$app->user->isGuest) {
            try { Yii::$app->sso->tryAutoLoginFromCookie(); } catch (\Throwable $e) {
                Yii::warning('SSO auto-login failed: '.$e->getMessage(), 'sso');
            }
            }
        },
        
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [],
        ],

    ],
    'params' => $params,



];
/*
if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
*/
return $config;
