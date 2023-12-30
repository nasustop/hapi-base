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
use Nasustop\HapiBase\Command\GenCodeCommand;
use Nasustop\HapiBase\Command\GenTemplateCommand;
use Nasustop\HapiBase\HttpServer\Request;
use Nasustop\HapiBase\HttpServer\RequestInterface;
use Nasustop\HapiBase\HttpServer\Response;
use Nasustop\HapiBase\HttpServer\ResponseInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                RequestInterface::class => Request::class,
                ResponseInterface::class => Response::class,
                CoreMiddleware::class => \Nasustop\HapiBase\Middleware\CoreMiddleware::class,
            ],
            'commands' => [
                GenCodeCommand::class,
                GenTemplateCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
