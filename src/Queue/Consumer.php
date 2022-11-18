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
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Nasustop\HapiBase\Queue\Amqp\ConnectionFactory;
use Nasustop\HapiBase\Queue\Amqp\Consumer as BaseConsumer;
use Nasustop\HapiBase\Queue\Message\AmqpMessage;
use Nasustop\HapiBase\Queue\Message\RedisMessage;
use Psr\Container\ContainerInterface;

class Consumer extends AbstractProcess
{
    protected string $queue = '';

    protected RedisMessage $redisMessage;

    protected AmqpMessage $amqpMessage;

    public function __construct(protected ContainerInterface $container)
    {
        if (empty($this->queue)) {
            $this->queue = self::class;
        }
        $this->initQueue();
        parent::__construct($container);
    }

    public function setQueue(string $queue): self
    {
        $this->queue = $queue;
        $this->initQueue();
        return $this;
    }

    public function isEnable($server): bool
    {
        return (bool) $this->getConfig('queue.open_process', false);
    }

    /**
     * get RedisMessage.
     */
    public function getRedisMessage(): RedisMessage
    {
        if (empty($this->redisMessage)) {
            $this->setRedisMessage(new RedisMessage());
        }
        return $this->redisMessage;
    }

    /**
     * set RedisMessage.
     */
    public function setRedisMessage(RedisMessage $redisMessage): self
    {
        $this->redisMessage = $redisMessage;
        return $this;
    }

    /**
     * get AmqpMessage.
     */
    public function getAmqpMessage(): AmqpMessage
    {
        if (empty($this->amqpMessage)) {
            $this->setAmqpMessage(new AmqpMessage());
        }
        return $this->amqpMessage;
    }

    /**
     * set AmqpMessage.
     */
    public function setAmqpMessage(AmqpMessage $amqpMessage): self
    {
        $this->amqpMessage = $amqpMessage;
        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        $queueDriver = $this->getConfig('queue.driver', 'redis');
        switch ($queueDriver) {
            case 'redis':
                $this->driverRedis();
                break;
            case 'amqp':
                $this->driverAmqp();
                break;
        }
    }

    protected function getConfig(string $key, $default = null)
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get($key, $default);
    }

    protected function initQueue()
    {
        $this->name = "queue.{$this->queue}";
        $this->nums = (int) $this->getConfig(sprintf('queue.queue.%s.process', $this->queue), $this->nums);
    }

    protected function driverRedis()
    {
        echo sprintf("Queue[%s] start...\n", $this->name);
        $this->getRedisMessage()->onQueue($this->queue)->consume();
    }

    /**
     * @throws \Throwable
     */
    protected function driverAmqp()
    {
        $consumer = make(
            BaseConsumer::class,
            [
                $this->container,
                $this->container->get(ConnectionFactory::class),
                $this->container->get(StdoutLoggerInterface::class),
            ]
        );
        $message = $this->getAmqpMessage()->onQueue($this->queue);
        echo sprintf("Queue[%s] start...\n", $this->name);
        $factory = $this->container->get(ConnectionFactory::class);
        $consumer->setFactory($factory)->consume($message);
    }
}
