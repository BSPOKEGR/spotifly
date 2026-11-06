<?php

use Monolog\Handler\NullHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack_dev'),
    'queue_default' => env('QUEUE_LOG_CHANGE', 'queue'),
    'queue_error' => env('QUEUE_LOG_CHANGE', 'queue_error'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'deprecations'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [
        'stack_production' => [
            'driver' => 'stack',
            'channels' => ['main', 'info', 'syslog', 'slack'],
            'ignore_exceptions' => false,
        ],

        'stack_dev' => [
            'driver' => 'stack',
            'channels' => ['main', 'info', 'syslog'],
            'ignore_exceptions' => false,
        ],

        'main' => [
            'driver' => 'custom',
            'via' => App\Logging\MainLogger::class,
            'permission' => 0666,
        ],

        // if we need to create a stack for queue message, that includes email and slack notifications
        // keep in mind that deduplication handler is not working as it is with http requests (requires some investigation)
        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => 'debug',
            'days' => 7,
            'permission' => 0666,
        ],

        'queue_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue_error.log'),
            'level' => 'error',
            'days' => 7,
            'permission' => 0666,
        ],

        'info' => [
            'driver' => 'custom',
            'via' => App\Logging\InfoLogger::class,
            'permission' => 0666,
        ],

        'slack' => [
            'driver' => 'custom',
            'via' => App\Logging\SlackLogger::class,
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'slack_channel' => env('LOG_SLACK_CHANNEL'),
            'username' => env('LOG_SLACK_USERNAME', 'tErrorBot'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'path' => storage_path('logs/slack_alerts.log'),
            'days' => 7,
            'permission' => 0666,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
            'permission' => 0666,
        ],

        'deprecations' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deprecations.log'),
            'level' => 'debug',
            'days' => 7,
            'permission' => 0666,
        ],
    ],

];
