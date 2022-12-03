<?php

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