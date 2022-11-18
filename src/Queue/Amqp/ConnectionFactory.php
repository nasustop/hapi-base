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
namespace Nasustop\HapiBase\Queue\Amqp;

class ConnectionFactory extends \Hyperf\Amqp\ConnectionFactory
{
    protected function getConfig(string $pool): array
    {
        $key = 'queue.amqp';
        if (! $this->config->has($key)) {
            throw new \InvalidArgumentException(sprintf('config[%s] is not exist!', $key));
        }

        return $this->config->get($key);
    }
}
