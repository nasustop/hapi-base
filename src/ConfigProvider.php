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

use Nasustop\HapiBase\Command\GenCodeCommand;
use Nasustop\HapiBase\HttpServer\Response;
use Nasustop\HapiBase\HttpServer\ResponseInterface;
use Nasustop\HapiBase\Queue\Amqp\Consumer;
use Nasustop\HapiBase\Queue\Amqp\ConsumerFactory;
use Nasustop\HapiBase\Queue\Amqp\Producer;
use Nasustop\HapiBase\Queue\Command\ConsumerCommand;
use Nasustop\HapiBase\Queue\Command\ProducerCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ResponseInterface::class => Response::class,
                Consumer::class => ConsumerFactory::class,
                Producer::class => Producer::class,
            ],
            'commands' => [
                ConsumerCommand::class,
                ProducerCommand::class,
                GenCodeCommand::class,
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
                    'id' => 'config',
                    'description' => 'The config for queue.',
                    'source' => __DIR__ . '/../publish/queue.php',
                    'destination' => BASE_PATH . '/config/autoload/queue.php',
                ],
            ],
        ];
    }
}
