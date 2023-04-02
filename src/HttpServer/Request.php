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
namespace Nasustop\HapiBase\HttpServer;

class Request extends \Hyperf\HttpServer\Request implements RequestInterface
{
    /**
     * 获取请求来源IP.
     */
    public function getRequestIp(): string
    {
        $headers = $this->getHeaders();
        $serverParams = $this->getServerParams();
        $ip = $serverParams['remote_addr'] ?? '';
        if (isset($headers['x-forwarded-for'][0]) && ! empty($headers['x-forwarded-for'][0])) {
            $ip = $headers['x-forwarded-for'][0];
        } elseif (isset($headers['x-real-ip'][0]) && ! empty($headers['x-real-ip'][0])) {
            $ip = $headers['x-real-ip'][0];
        }
        return $ip;
    }

    /**
     * 获取当前请求的alias.
     */
    public function getRequestApiAlias(): string
    {
        $attributes = $this->getAttributes();
        $api_alias = '';
        $router = $attributes['Hyperf\HttpServer\Router\Dispatched'] ?? null;
        if ($router instanceof \Hyperf\HttpServer\Router\Dispatched) {
            $api_alias = $router->handler->options['alias'] ?? '';
        }

        return $api_alias;
    }

    /**
     * 获取当前请求的name.
     */
    public function getRequestApiName(): string
    {
        $attributes = $this->getAttributes();
        $api_name = '';
        $router = $attributes['Hyperf\HttpServer\Router\Dispatched'] ?? null;
        if ($router instanceof \Hyperf\HttpServer\Router\Dispatched) {
            $api_name = $router->handler->options['name'] ?? '';
        }

        return $api_name;
    }
}
