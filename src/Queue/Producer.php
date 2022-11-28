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
namespace Nasustop\HapiBase\Queue;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Nasustop\HapiBase\Queue\Job\JobInterface;
use Nasustop\HapiBase\Queue\Message\AmqpMessage;
use Nasustop\HapiBase\Queue\Message\RedisMessage;

class Producer
{
    protected RedisMessage $redisMessage;

    protected AmqpMessage $amqpMessage;

    public function __construct(protected JobInterface $payload, protected string $queue = 'default')
    {
        $this->initPayloadQueue();
    }

    /**
     * set Job.
     */
    public function setPayload(JobInterface $job): self
    {
        $this->payload = $job;
        $this->initPayloadQueue();
        return $this;
    }

    /**
     * set Queue.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        $this->initPayloadQueue();
        return $this;
    }

    public function dispatcher()
    {
        switch ($this->getConfig('queue.driver', 'redis')) {
            case 'redis':
                $this->dispatcherRedis();
                break;
            case 'amqp':
                $this->dispatcherAmqp();
                break;
            default:
                throw new \InvalidArgumentException('queue.driver config error');
        }
    }

    protected function initPayloadQueue()
    {
        $this->payload = $this->payload->setQueue($this->queue);
    }

    protected function dispatcherRedis(): bool
    {
        return (new RedisMessage($this->payload))
            ->onQueue($this->queue)
            ->dispatcher();
    }

    protected function dispatcherAmqp(): bool
    {
        return (new AmqpMessage($this->payload))
            ->onQueue($this->queue)
            ->dispatcher();
    }

    protected function getConfig(string $key, $default = null)
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        return $config->get($key, $default);
    }
}
