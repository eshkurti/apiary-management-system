<?php

declare(strict_types=1);

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
            // Production serves this app under /admin behind a reverse proxy, so
            // backend/config/main.php pins cookies to that path. PHP's built-in
            // server (`php -S`, used for local development per SETUP_GUIDE.md)
            // serves the app from the domain root instead — a /admin-scoped
            // cookie is never sent back on root-served requests, which breaks
            // CSRF validation and makes login fail with a 400 on every attempt.
            // Overriding back to '/' here fixes local dev without touching the
            // production-facing value in backend/config/main.php.
            'csrfCookie' => [
                'path' => '/',
            ],
        ],
        'user' => [
            'identityCookie' => [
                'path' => '/',
            ],
        ],
        'session' => [
            'cookieParams' => [
                'path' => '/',
            ],
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;
