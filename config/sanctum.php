<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

return [
    'stateful' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'SANCTUM_STATEFUL_DOMAINS',
            'localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,::1,'.Sanctum::currentApplicationUrlWithPort(),
        )),
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],
];
