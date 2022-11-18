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

use Nasustop\HapiBase\Queue\Job\Job;

class DemoJob extends Job
{
    public function __construct(protected array $data)
    {
        var_dump('demo job init: ' . date('Y-m-d H:i:s'));
    }

    public function handle(): string
    {
        var_dump('demo job handle: ' . date('Y-m-d H:i:s'));
        var_dump($this->data);
        return self::ACK;
    }
}
