<?php

return [

    'app_key' => env('BUS_APP_KEY'),
    'app_secret' => env('BUS_APP_SECRET'),

    'targets' => [
        'master' => [
            'base_url' => env('MASTER_APP_URL'),
            'endpoint' => '/api/bus/webhook',
        ],
        'sidbm' => [
            'base_url' => env('SIDBM_APP_URL'),
            'endpoint' => '/api/bus/webhook',
        ],
        'lkm' => [
            'base_url' => env('LKM_APP_URL'),
            'endpoint' => '/api/bus/webhook',
        ],
        'siupk' => [
            'base_url' => env('SIUPK_APP_URL'),
            'endpoint' => '/api/bus/webhook',
        ],
    ],

    'max_attempts' => 5,

    'backoff' => [10, 30, 60, 300, 900],

    'request_timeout' => 10,

    'signature_skew_seconds' => 300,
];
