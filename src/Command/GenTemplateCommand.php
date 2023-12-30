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
namespace Nasustop\HapiBase\Command;

use Hyperf\Command\Command as HyperfCommand;
use Nasustop\HapiBase\Command\GenerateCode\GenTemplate;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenTemplateCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('hapi:gen:template');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('快速生成代码文件');
        $this->addArgument('table_name', InputArgument::REQUIRED, '表名');
        $this->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, '使用哪一个bundle.', 'SystemBundle');
        $this->setHelp('php bin/hyperf.php system:gen:template [table_name] [-b [bundle]]');
        $this->addUsage('[table_name]表名');
    }

    public function handle()
    {
        $table_name = $this->input->getArgument('table_name');
        $bundle = $this->input->getOption('bundle');
        $genModel = new GenTemplate();
        $modelClass = $genModel->createTemplate($bundle, $table_name);
        $this->info("生成文件{$modelClass}");
    }
}
