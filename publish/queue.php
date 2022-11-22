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
    'driver' => env('QUEUE_DRIVER', 'redis'),
    'open_process' => env('QUEUE_PROCESS', false),
    'logger' => [
        'name' => 'queue',
        'group' => 'default',
    ],
    'redis' => [
        'redis' => [
            'pool' => 'default',
        ],
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'concurrent' => [
            'limit' => 10,
        ],
    ],
    'amqp' => [
        'host' => env('AMQP_HOST', 'localhost'),
        'port' => (int) env('AMQP_PORT', 5672),
        'user' => env('AMQP_USER', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest'),
        'vhost' => env('AMQP_VHOST', '/'),
        'concurrent' => [
            'limit' => 1,
        ],
        'pool' => [
            'connections' => 2,
        ],
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            'connection_timeout' => 3,
            'read_write_timeout' => 6,
            'context' => null,
            'keepalive' => true,
            'heartbeat' => 3,
            'channel_rpc_timeout' => 0.0,
            'close_on_destruct' => false,
            'max_idle_channels' => 10,
        ],
    ],
    'queue' => [
        'default' => [
            'process' => 3, // open_process时单台机器开启消费队列的进程数
            // TODO: 可以加上queue.redis的所有配置，默认会覆盖queue.redis的配置
        ],
        // 延迟多少秒消费【延迟队列名称应加上_delayed_秒数，如：default_delayed_5】
        'default_delayed_5' => [
            'process' => 3, // open_process时单台机器开启消费队列的进程数
        ],
    ],
];
