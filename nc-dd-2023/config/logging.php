<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path(
                sprintf('logs/%s.log', env('APP_PROJECT', 'laravel'))
            ),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'sentry' => [
            'driver' => 'sentry',
            'level'  => 'debug',
            'bubble' => true,
        ],

        'graylog' => [
            'driver' => 'custom',
            'via'    => \Hedii\LaravelGelfLogger\GelfLoggerFactory::class,

            // This optional option determines the processors that should be
            // pushed to the handler. This option is useful to modify a field
            // in the log context (see NullStringProcessor), or to add extra
            // data. Each processor must be a callable or an object with an
            // __invoke method: see monolog documentation about processors.
            // Default is an empty array.
            'processors' => [
                \Hedii\LaravelGelfLogger\Processors\NullStringProcessor::class,
                \Hedii\LaravelGelfLogger\Processors\RenameIdFieldProcessor::class,
                \App\Utils\Graylog\Processors\UserProcessor::class,
                \App\Utils\Graylog\Processors\EnvironmentProcessor::class,

                // another processor...
            ],

            // This optional option determines the minimum "level" a message
            // must be in order to be logged by the channel. Default is 'debug'
            'level' => 'debug',

            // This optional option determines the channel name sent with the
            // message in the 'facility' field. Default is equal to app.env
            // configuration value
            'name' => sprintf(
                '[%s] %s',
                env('APP_USER', 'dev'),
                env('APP_NAME', 'dev'),
            ),

            // This optional option determines the system name sent with the
            // message in the 'source' field. When forgotten or set to null,
            // the current hostname is used.
            'system_name' => null,

            // This optional option determines if you want the UDP, TCP or HTTP
            // transport for the gelf log messages. Default is UDP
            'transport' => env('LOG_GRAYLOG_TRANSPORT', 'udp'),

            // This optional option determines the host that will receive the
            // gelf log messages. Default is 127.0.0.1
            'host' => env('LOG_GRAYLOG_HOST', '127.0.0.1'),

            // This optional option determines the port on which the gelf
            // receiver host is listening. Default is 12201
            'port' => env('LOG_GRAYLOG_PORT', '127.0.0.1'),

            // This optional option determines the chunk size used when
            // transferring message via UDP transport. Default is 1420.
            'chunk_size' => 1420,

            // This optional option determines the path used for the HTTP
            // transport. When forgotten or set to null, default path '/gelf'
            // is used.
            'path' => '/gelf',

            // This optional option enable or disable ssl on TCP or HTTP
            // transports. Default is false.
            'ssl' => false,

            // If ssl is enabled, the following configuration is used.
            'ssl_options' => [
                // Enable or disable the peer certificate check. Default is
                // true.
                'verify_peer' => true,

                // Path to a custom CA file (eg: "/path/to/ca.pem"). Default
                // is null.
                'ca_file' => null,

                // List of ciphers the SSL layer may use, formatted as
                // specified in ciphers(1). Default is null.
                'ciphers' => null,

                // Whether self-signed certificates are allowed. Default is
                // false.
                'allow_self_signed' => false,
            ],

            // This optional option determines the maximum length per message
            // field. When forgotten or set to null, the default value of
            // \Monolog\Formatter\GelfMessageFormatter::DEFAULT_MAX_LENGTH is
            // used (currently this value is 32766)
            'max_length' => null,

            // This optional option determines the prefix for 'context' fields
            // from the Monolog record. Default is null (no context prefix)
            'context_prefix' => null,

            // This optional option determines the prefix for 'extra' fields
            // from the Monolog record. Default is null (no extra prefix)
            'extra_prefix' => null,

            // This optional option determines whether errors thrown during
            // logging should be ignored or not. Default is true.
            'ignore_error' => true,

        ],
    ],

];
