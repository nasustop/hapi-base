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
namespace Nasustop\HapiBase\Command\GenerateCode;

use Hyperf\Utils\CodeGen\Project;
use Hyperf\Utils\Str;

class GenService extends AbstractGen
{
    public function createService(string $bundle, string $table)
    {
        $repository = $this->genNamespace($bundle, $table, 'Repository');
        $project = new Project();
        // singular复数单词转成单数
        // studly将下划线或中划线转成大驼峰
        $class = Str::studly(Str::singular($table)) . 'Service';
        // 根据文件路径获取命名空间
        $class = $project->namespace('src/' . $bundle . '/Service') . $class;
        // 获取绝对路径
        $path = BASE_PATH . '/' . $project->path($class);

        if (! file_exists($path)) {
            $this->mkdir($path);
        }
        // 替换模板基础数据并填充到文件中
        $stubs = $this->buildClass($table, $class, $repository);

        if (! is_file($path)) {
            file_put_contents($path, $stubs);
        }

        return $class;
    }

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $table, string $name, string $repository): string
    {
        $repository_class = str_replace($this->getNamespace($repository) . '\\', '', $repository);
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Service.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceUses($stub, $repository)
            ->replaceClass($stub, $name)
            ->replaceInjectClass($stub, $repository_class);
    }
}
