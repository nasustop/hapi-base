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
namespace Nasustop\HapiBase\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CoreMiddleware extends \Hyperf\HttpServer\CoreMiddleware
{
    public function dispatch(ServerRequestInterface $request): ServerRequestInterface
    {
        $request = parent::dispatch($request);
        return $this->addRouteAlias($request);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = parent::process($request, $handler);
        return $response->withHeader('Server', env('APP_NAME', 'hapi'));
    }

    protected function addRouteAlias(ServerRequestInterface $request): ServerRequestInterface
    {
        $attributes = $request->getAttributes();
        $attributes = @json_decode(json_encode($attributes), true);
        $alias = $attributes ? ($attributes['Hyperf\HttpServer\Router\Dispatched']['handler']['options']['alias'] ?? '') : '';
        $name = $attributes ? ($attributes['Hyperf\HttpServer\Router\Dispatched']['handler']['options']['name'] ?? '') : '';
        // 获取路由文件中设置的别名和名称
        return $request->withAttribute('route.alias', $alias)->withAttribute('route.name', $name);
    }
}
