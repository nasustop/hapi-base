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
namespace Nasustop\HapiBase;

use Hyperf\HttpServer\CoreMiddleware;
use Nasustop\HapiBase\Auth\AuthManagerFactory;
use Nasustop\HapiBase\Command\GenCodeCommand;
use Nasustop\HapiBase\HttpServer\Request;
use Nasustop\HapiBase\HttpServer\RequestInterface;
use Nasustop\HapiBase\HttpServer\Response;
use Nasustop\HapiBase\HttpServer\ResponseInterface;
use Nasustop\HapiBase\Memcached\Memcached;
use Nasustop\HapiBase\Middleware\CoreMiddleware as HapiCoreMiddleware;
use Nasustop\HapiBase\Queue\Amqp\Consumer;
use Nasustop\HapiBase\Queue\Amqp\ConsumerFactory;
use Nasustop\HapiBase\Queue\Amqp\Producer;
use Nasustop\HapiBase\Queue\Command\ConsumerCommand;
use Nasustop\HapiBase\Queue\Command\ProducerCommand;
use Nasustop\HapiBase\Queue\Listener\QueueHandleListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                RequestInterface::class => Request::class,
                ResponseInterface::class => Response::class,
                Consumer::class => ConsumerFactory::class,
                Producer::class => Producer::class,
                CoreMiddleware::class => HapiCoreMiddleware::class,
                AuthManagerFactory::class => AuthManagerFactory::class,
                \Memcached::class => Memcached::class,
            ],
            'commands' => [
                ConsumerCommand::class,
                ProducerCommand::class,
                GenCodeCommand::class,
            ],
            'listeners' => [
                QueueHandleListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'auth',
                    'description' => 'The config for auth.',
                    'source' => __DIR__ . '/../publish/auth.php',
                    'destination' => BASE_PATH . '/config/autoload/auth.php',
                ],
                [
                    'id' => 'queue',
                    'description' => 'The config for queue.',
                    'source' => __DIR__ . '/../publish/queue.php',
                    'destination' => BASE_PATH . '/config/autoload/queue.php',
                ],
                [
                    'id' => 'memcached',
                    'description' => 'The config for memcached.',
                    'source' => __DIR__ . '/../publish/memcached.php',
                    'destination' => BASE_PATH . '/config/autoload/memcached.php',
                ],
            ],
        ];
    }
}
