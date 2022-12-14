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

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Constants;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Amqp\Packer\Packer;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Nasustop\HapiBase\Queue\Amqp\ConnectionFactory;
use Nasustop\HapiBase\Queue\Amqp\Consumer;
use Nasustop\HapiBase\Queue\Amqp\Producer;
use Nasustop\HapiBase\Queue\Job\JobInterface;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpMessage extends ConsumerMessage implements ProducerMessageInterface
{
    protected array $properties = [
        'content_type' => 'text/plain',
        'delivery_mode' => Constants::DELIVERY_MODE_PERSISTENT,
    ];

    protected int $millisecond = 0;

    protected static bool $declare_status = false;

    public function __construct(protected ?JobInterface $payload = null)
    {
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setPayload($data): AmqpMessage
    {
        if (! $data instanceof JobInterface) {
            throw new \InvalidArgumentException('job的数据类型错误');
        }
        $this->payload = $data;
        return $this;
    }

    public function payload(): string
    {
        return $this->serialize();
    }

    /**
     * get Job.
     */
    public function job(): JobInterface
    {
        return $this->payload;
    }

    public function serialize(): string
    {
        $packer = ApplicationContext::getContainer()->get(Packer::class);
        return $packer->pack(serialize($this->payload));
    }

    public function unserialize(string $data)
    {
        $result = parent::unserialize($data); // TODO: Change the autogenerated stub
        return unserialize($result);
    }

    public function consume($data): string
    {
        if (! $data instanceof JobInterface) {
            throw new \InvalidArgumentException('队列接收的数据类型错误');
        }
        return $data->handle();
    }

    /**
     * Set the delay time.
     * @return $this
     */
    public function setDelayMs(int $millisecond, string $name = 'x-delay'): AmqpMessage
    {
        if (empty($millisecond)) {
            return $this;
        }
        $this->millisecond = $millisecond;
        $this->properties['application_headers'] = new AMQPTable([$name => $millisecond]);
        return $this;
    }

    /**
     * Overwrite.
     */
    public function getExchangeBuilder(): ExchangeBuilder
    {
        if (empty($this->millisecond)) {
            return parent::getExchangeBuilder();
        }
        return (new ExchangeBuilder())->setExchange($this->getExchange())
            ->setType('x-delayed-message')
            ->setArguments(new AMQPTable(['x-delayed-type' => $this->getType()]));
    }

    /**
     * Overwrite.
     */
    public function getQueueBuilder(): QueueBuilder
    {
        if (empty($this->millisecond)) {
            return parent::getQueueBuilder();
        }
        return (new QueueBuilder())->setQueue($this->getQueue())
            ->setArguments(new AMQPTable(['x-dead-letter-exchange' => $this->getDeadLetterExchange()]));
    }

    public function onQueue(string $queue): AmqpMessage
    {
        $this->setQueue($queue);
        $config = $this->getConfig(sprintf('queue.queue.%s', $queue));
        if (empty($config)) {
            throw new \InvalidArgumentException(sprintf('queue config [%s] is not exist!', $queue));
        }
        $app_name = (string) $this->getConfig('app_name');
        $exchange = sprintf('%s.exchange.%s', $app_name, $queue);
        $routingKey = sprintf('%s.routing_key.%s', $app_name, $queue);
        $queue = sprintf('%s.queue.%s', $app_name, $queue);
        $delayQueue = explode('_delayed_', $queue);
        $delay = $delayQueue[1] ?? 0;
        return $this
            ->setExchange($exchange)
            ->setRoutingKey($routingKey)
            ->setQueue($queue)
            ->setDelayMs($delay * 1000);
    }

    public function declare(): AmqpMessage
    {
        if (self::$declare_status) {
            return $this;
        }
        $consumer = ApplicationContext::getContainer()->get(Consumer::class);
        $factory = ApplicationContext::getContainer()->get(ConnectionFactory::class);
        $connection = $factory->getConnection($this->getPoolName());

        $channel = $connection->getConfirmChannel();
        $consumer->setFactory($factory)->declare($this, $channel);
        self::$declare_status = true;
        return $this;
    }

    public function dispatcher(): bool
    {
        $producer = ApplicationContext::getContainer()->get(Producer::class);
        $factory = ApplicationContext::getContainer()->get(ConnectionFactory::class);
        return $producer->setFactory($factory)->produce($this->declare());
    }

    protected function getConfig(string $key, $default = null)
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        return $config->get($key, $default);
    }

    protected function getDeadLetterExchange(): string
    {
        return 'delayed';
    }
}
