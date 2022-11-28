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
namespace Nasustop\HapiBase\Queue\Listener;

use Hyperf\Amqp\Event\AfterConsume;
use Hyperf\Amqp\Event\BeforeConsume;
use Hyperf\Amqp\Event\ConsumeEvent;
use Hyperf\Amqp\Event\FailToConsume;
use Hyperf\AsyncQueue\AnnotationJob;
use Hyperf\AsyncQueue\Event\AfterHandle;
use Hyperf\AsyncQueue\Event\BeforeHandle;
use Hyperf\AsyncQueue\Event\Event;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\AsyncQueue\Event\RetryHandle;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Logger\LoggerFactory;
use Nasustop\HapiBase\Queue\Job\JobInterface;
use Nasustop\HapiBase\Queue\Message\AmqpMessage;
use Psr\Container\ContainerInterface;

class QueueHandleListener implements ListenerInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected LoggerFactory $loggerFactory,
        protected ConfigInterface $config,
        protected FormatterInterface $formatter,
        protected StdoutLoggerInterface $stdoutLogger,
    ) {
    }

    public function listen(): array
    {
        return [
            AfterHandle::class,
            BeforeHandle::class,
            FailedHandle::class,
            RetryHandle::class,
            BeforeConsume::class,
            AfterConsume::class,
            FailToConsume::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof Event) {
            $job = $event->getMessage()->job();
            if ($job instanceof JobInterface) {
                $jobClass = get_class($job);
                if ($job instanceof AnnotationJob) {
                    $jobClass = sprintf('Job[%s@%s]', $job->class, $job->method);
                }
                $date = date('Y-m-d H:i:s');

                $logger = $this->loggerFactory->get($job->getQueue(), $this->config->get('queue.logger', 'default'));

                switch (true) {
                    case $event instanceof BeforeHandle:
                        $logger->info(sprintf('[%s] Processing Redis job [%s].', $date, $jobClass));
                        $this->stdoutLogger->info(sprintf('[%s] Processing Redis job [%s].', $date, $jobClass));
                        break;
                    case $event instanceof AfterHandle:
                        $logger->info(sprintf('[%s] Processed Redis Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->info(sprintf('[%s] Processed Redis Job [%s].', $date, $jobClass));
                        break;
                    case $event instanceof FailedHandle:
                        $logger->error(sprintf('[%s] Failed Redis Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->error(sprintf('[%s] Failed Redis Job [%s].', $date, $jobClass));
                        $logger->error($this->formatter->format($event->getThrowable()));
                        $this->stdoutLogger->error($this->formatter->format($event->getThrowable()));
                        break;
                    case $event instanceof RetryHandle:
                        $logger->warning(sprintf('[%s] Retried Redis Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->warning(sprintf('[%s] Retried Redis Job [%s].', $date, $jobClass));
                        break;
                }
            }
        }
        if ($event instanceof ConsumeEvent) {
            if ($event->getMessage() instanceof AmqpMessage && $event->getMessage()->job() instanceof JobInterface) {
                $job = $event->getMessage()->job();
                $jobClass = get_class($job);
                if ($job instanceof AnnotationJob) {
                    $jobClass = sprintf('Job[%s@%s]', $job->class, $job->method);
                }
                $date = date('Y-m-d H:i:s');
                $logger = $this->loggerFactory->get($job->getQueue(), $this->config->get('queue.logger', 'default'));

                switch (true) {
                    case $event instanceof BeforeConsume:
                        $logger->info(sprintf('[%s] Processing Amqp Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->info(sprintf('[%s] Processing Amqp Job [%s].', $date, $jobClass));
                        break;
                    case $event instanceof AfterConsume:
                        $logger->info(sprintf('[%s] Processed Amqp Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->info(sprintf('[%s] Processed Amqp Job [%s].', $date, $jobClass));
                        break;
                    case $event instanceof FailToConsume:
                        $logger->warning(sprintf('[%s] Failed Amqp Job [%s].', $date, $jobClass));
                        $this->stdoutLogger->warning(sprintf('[%s] Failed Amqp Job [%s].', $date, $jobClass));
                        break;
                }
            }
        }
    }
}
