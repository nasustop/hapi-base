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

class Consumer extends \Hyperf\Amqp\Consumer
{
    /**
     * set Factory.
     */
    public function setFactory(ConnectionFactory $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * get Factory.
     */
    public function getFactory(): \Hyperf\Amqp\ConnectionFactory
    {
        return $this->factory;
    }
}
