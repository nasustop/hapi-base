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
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class GenRepository extends AbstractGen
{
    public function createRepository(string $bundle, string $table): string
    {
        $model = $this->genNamespace($bundle, $table, 'Model');
        $project = new Project();
        // singular复数单词转成单数
        // studly将下划线或中划线转成大驼峰
        $class = Str::studly(Str::singular($table)) . 'Repository';
        // 根据文件路径获取命名空间
        $class = $project->namespace('src/' . $bundle . '/Repository') . $class;
        // 获取绝对路径
        $path = BASE_PATH . '/' . $project->path($class);

        if (! file_exists($path)) {
            $this->mkdir($path);
        }
        // 替换模板基础数据并填充到文件中
        $stubs = $this->buildClass($table, $class, $model);
        file_put_contents($path, $stubs);

        $builder = $this->getSchemaBuilder($this->pool);
        // 获取某张表的表结构数组,并将这个结构数组的key转成小写[基本用不到]
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        $stms = (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse(file_get_contents($path));
        // 将文件解析到抽象语法树类上面。
        // 应该是用的这个：https://github.com/nikic/PHP-Parser
        // hyperf实现了部分Visitor的代码
        $traverser = new NodeTraverser();
        $stms = $traverser->traverse($stms);
        $code = (new Standard())->prettyPrintFile($stms);

        file_put_contents($path, $code);

        return $class;
    }

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $table, string $name, string $model): string
    {
        $model_class = str_replace($this->getNamespace($model) . '\\', '', $model);
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Repository.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceUses($stub, $model)
            ->replaceClass($stub, $name)
            ->replaceInjectClass($stub, $model_class);
    }
}
