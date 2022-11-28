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
namespace Nasustop\HapiBase\Queue\Job;

interface JobInterface extends \Hyperf\AsyncQueue\JobInterface
{
    public function setQueue(string $queue): self;

    public function getQueue(): string;

    public function handle(): string;
}
