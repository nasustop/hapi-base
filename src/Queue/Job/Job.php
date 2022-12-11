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

abstract class Job implements JobInterface
{
    /**
     * Acknowledge the message.
     */
    public const ACK = 'ack';

    /**
     * Unacknowledged the message.
     */
    public const NACK = 'nack';

    /**
     * Reject the message and requeue it.
     */
    public const REQUEUE = 'requeue';

    /**
     * Reject the message and drop it.
     */
    public const DROP = 'drop';

    protected int $attempts = 1;

    protected string $queue = 'default';

    public function getMaxAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * get Queue.
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * set Queue.
     */
    public function setQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }
}
