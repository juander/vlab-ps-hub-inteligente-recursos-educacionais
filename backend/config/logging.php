<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/laravel.log'),
            ],
            'formatter' => JsonFormatter::class,
            'level' => env('LOG_LEVEL', 'info'),
        ],
    ],
];
