<?php

return [
    'bsVersion' => '5.x',
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
     // เพิ่ม config สำหรับ API auth
    'authApi' => [
        'loginPath' => '/authen/login',
        'timeout'   => 10,
        'verifySSL' => true,
    ],
];
