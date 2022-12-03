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

use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends HttpResponseInterface
{
    /**
     * Format data to SUCCESS JSON and return data with Content-Type:application/json header.
     */
    public function success(array|string|int|bool $data): PsrResponseInterface;

    /**
     * Format data to ERROR JSON and return data with Content-Type:application/json header.
     */
    public function error(array|string|int|bool $data, int $code = 500, string $msg = 'Server Error!'): PsrResponseInterface;
}
