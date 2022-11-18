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
namespace Nasustop\HapiBase\Queue\Demo;

use Nasustop\HapiBase\Queue\Consumer;

class DemoConsumer extends Consumer
{
    protected string $queue = 'default';

    public function isEnable($server): bool
    {
        return true;
    }
}
