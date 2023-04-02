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

class Response extends \Hyperf\HttpServer\Response implements ResponseInterface
{
    public function success(array|string|int|bool $data): \Psr\Http\Message\ResponseInterface
    {
        $result = [
            'code' => 0,
            'msg' => 'success',
            'data' => $data,
        ];
        return $this->json($result);
    }

    public function error(array|string|int|bool $data, int $code = 500, string $msg = 'Server Error!'): \Psr\Http\Message\ResponseInterface
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
        return $this->json($result);
    }
}
