<?php

$params = require __DIR__ . '/params.php';
$db     = require __DIR__ . '/db.php';

$config = [
    'id' => 'Lasc SSKRU',
    'name' => 'ศูนย์วิจัย LASC มหาวิทยาลัยราชภัฏศรีสะเกษ',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'layout' => 'berry',
    'components' => [
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'request' => [
            'cookieValidationKey' => '1smD3uuUUKbmNvh_mUhJnUW3qMAI-IUC', // TODO: เปลี่ยนเป็นค่า secret จริง
            'baseUrl' => '/research', // ให้ตรงกับโฟลเดอร์ใน URL
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        
        'user' => [
            'identityClass'   => \app\models\Account::class,   // ✅ ใช้ Account เป็นหลัก
            'enableSession'   => true,
            'enableAutoLogin' => false,
            'loginUrl'        => ['site/login'],
            'identityCookie'  => [
                'name' => '_identity_research',
                'httpOnly' => true,
                'sameSite' => 'Lax',
            ],
        ],
        'session' => [
            'name' => 'research-ssid',
            'timeout' => 60 * 60 * 8,
            'cookieParams' => [
                'httpOnly' => true,
                'sameSite' => 'Lax',
            ],
        ],

        // httpclient ทั่วไป
        'httpClient' => [
            'class' => \yii\httpclient\Client::class,
            'transport' => \yii\httpclient\CurlTransport::class,
        ],

        // ไคลเอนต์เรียก API ภายนอก (แนบ Bearer จาก identity ถ้ามี)
        'apiClient' => [
            'class' => \yii\httpclient\Client::class,
            'baseUrl' => 'https://sci-sskru.com', // TODO: ปรับให้ตรงระบบจริง
            'transport' => \yii\httpclient\CurlTransport::class,
            'formatters' => [
                \yii\httpclient\Client::FORMAT_JSON => \yii\httpclient\JsonFormatter::class,
            ],
            'on beforeSend' => function ($e) {
                $id = Yii::$app->user->identity;
                if ($id && !empty($id->access_token) && !$e->request->getHeaders()->has('Authorization')) {
                    $e->request->addHeaders(['Authorization' => 'Bearer ' . $id->access_token]);
                }
            },
        ],

        // service ดึงโปรไฟล์ผ่าน POST /authen/profile
        'apiAuth' => [
            'class' => \app\components\ApiAuthService::class,
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],

        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
            ],
        ],
    ],

    'params' => array_merge($params, [
        'hrmApiBase' => 'https://sci-sskru.com/authen',
        'ssoLoginUrl'   => 'https://sci-sskru.com/hrm/login',
        'ssoProfileUrl' => 'https://sci-sskru.com/authen/profile', // POST + Bearer + {personal_id}
    ]),
];


/*
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}
*/

return $config;
