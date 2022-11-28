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
namespace Nasustop\HapiBase\Queue\Message;

use Hyperf\AsyncQueue\Driver\RedisDriver;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Nasustop\HapiBase\Queue\Job\JobInterface;

class RedisMessage
{
    protected RedisDriver $driver;

    protected array $config = [];

    protected string $queue = 'default';

    public function __construct(protected ?JobInterface $payload = null)
    {
        $this->config = $this->getBaseConfig('queue.redis', []);
    }

    /**
     * set Queue.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function setPayload(JobInterface $job): self
    {
        $this->payload = $job;
        return $this;
    }

    /**
     * get Config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * set Config.
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * get Driver.
     */
    public function getDriver(): RedisDriver
    {
        if (empty($this->driver)) {
            $this->config = array_merge($this->config, $this->getBaseConfig(sprintf('queue.queue.%s', $this->queue), []));
            $this->config['channel'] = $this->config['channel'] ?? sprintf('{%s.queue.%s}', $this->getBaseConfig('app_name'), $this->queue);
            $this->setDriver(make(RedisDriver::class, ['config' => $this->config]));
        }
        return $this->driver;
    }

    /**
     * set Driver.
     */
    public function setDriver(RedisDriver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * 推送消息到队列中.
     */
    public function dispatcher(): bool
    {
        $delayQueue = explode('_delayed_', $this->queue);
        $delay = $delayQueue[1] ?? 0;
        $delay = intval($delay);

        return $this->getDriver()->push($this->payload, $delay);
    }

    /**
     * 消费队列.
     * 执行job的handle方法.
     */
    public function consume()
    {
        $this->getDriver()->consume();
    }

    protected function getBaseConfig(string $key, $default = null)
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        return $config->get($key, $default);
    }
}
