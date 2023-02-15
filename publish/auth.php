<?php

declare(strict_types=1);
/**
 * This file is part of HapiBase.
 *
 * @link     https://www.nasus.top
 * @document https://wiki.nasus.top
 * @contact  xupengfei@xupengfei.net
 * @license  https://github.com/nasustop/hapi-base/blob/master/LICENSE
 */
return [
    'default' => [
        'provider' => \Nasustop\HapiBase\Auth\UserProvider::class,
        'jwt' => [
            'alg' => env('JWT_ALG', 'HS256'),
            'secret' => env('JWT_SECRET', 'hapi'),
            'private_key' => env('JWT_PRIVATE_KEY'),
            'public_key' => env('JWT_PUBLIC_KEY'),
            'iss' => env('JWT_ISS', 'hapi'),
            'aud' => env('JWT_AUD', 'hapi'),
            'exp' => env('JWT_EXPIRED', 7200),
            'leeway' => env('JWT_LEEWAY', 300),
            'header' => env('JWT_HEADER', 'authorization'),
            'prefix' => env('JWT_PREFIX', 'bear'),
        ],
    ],
];
