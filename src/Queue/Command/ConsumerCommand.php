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
namespace Nasustop\HapiBase\Queue\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Nasustop\HapiBase\Queue\Consumer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

class ConsumerCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('hapi:queue:work');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('执行队列监听');
        $this->addArgument('queue', InputArgument::REQUIRED, '队列名称');
        $this->setHelp('php bin/hyperf.php hapi:queue:work [queue]');
        $this->addUsage('[queue]队列名称');
    }

    public function handle()
    {
        $queue = $this->input->getArgument('queue');
        $config = $this->getConfig(sprintf('queue.queue.%s', $queue));
        if (! $config) {
            $this->error(sprintf('[%s]队列配置不存在', $queue));
            return;
        }
        (new Consumer(ApplicationContext::getContainer()))->setQueue($queue)->handle();
    }

    protected function getConfig(string $key, $default = null)
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get($key, $default);
    }
}
