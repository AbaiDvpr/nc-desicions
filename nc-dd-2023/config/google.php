<?php declare(strict_types = 1);

/**
 * Конфиги google
 */
return [

    'recaptcha' => [
        'url'       => 'https://www.google.com/recaptcha/api/siteverify',
        'timeout'   => 5,
        'siteKey'   => env('GOOGLE_RECAPTCHA_SITE_KEY'),
        'secretKey' => env('GOOGLE_RECAPTCHA_SECRET_KEY'),
    ],

];
