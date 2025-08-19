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
        'hrmApi' => [
            'class' => 'app\components\HRMApiService',
        ],
        'apiAuth' => [
            'class' => app\components\ApiAuthService::class,
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '1smD3uuUUKbmNvh_mUhJnUW3qMAI-IUC',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            //'identityClass' => 'app\models\User',
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'apiClient' => [
            'class' => \yii\httpclient\Client::class,
            'baseUrl' => 'https://sci-sskru.com',
            'transport' => \yii\httpclient\CurlTransport::class, // หรือ StreamTransport
            'formatters' => [
                \yii\httpclient\Client::FORMAT_JSON => \yii\httpclient\JsonFormatter::class,
            ],
            'on beforeSend' => function ($event) {
                $id = Yii::$app->user->identity;
                if ($id && !empty($id->access_token) && !$event->request->getHeaders()->has('Authorization')) {
                    $event->request->addHeaders(['Authorization' => 'Bearer '.$id->access_token]);
                }
            },
        ],
        'apiAuth' => [
            'class' => app\components\ApiAuthService::class,
        ],
        // (ถ้าต้องการแคช)
        'cache' => ['class' => \yii\caching\FileCache::class],
        
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
