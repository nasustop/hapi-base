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

use Hyperf\HttpServer\Contract\RequestInterface as HttpRequestInterface;

interface RequestInterface extends HttpRequestInterface
{
    /**
     * 获取请求来源IP.
     */
    public function getRequestIp(): string;

    /**
     * 获取当前请求的alias.
     */
    public function getRequestApiAlias(): string;

    /**
     * 获取当前请求的name.
     */
    public function getRequestApiName(): string;
}
