<?php

declare(strict_types=1);

/**
 * Horizon configuration placeholder.
 * Horizon requires ext-pcntl (Linux/macOS). On Windows, use `php artisan queue:work`.
 * Install on production: composer require laravel/horizon
 */
return [
    'use' => 'default',
    'path' => 'horizon',
    'middleware' => ['web'],
    'waits' => [
        'redis:default' => 60,
        'redis:high' => 30,
        'redis:sync' => 30,
        'redis:notifications' => 60,
        'redis:pdf' => 120,
    ],
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'sync', 'notifications', 'pdf'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
