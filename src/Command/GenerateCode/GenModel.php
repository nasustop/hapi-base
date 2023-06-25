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

use Hyperf\CodeParser\Project;
use Hyperf\Database\Commands\Ast\ModelRewriteConnectionVisitor;
use Hyperf\Database\Commands\Ast\ModelUpdateVisitor;
use Hyperf\Database\Commands\ModelData;
use Hyperf\Database\Commands\ModelOption;
use Hyperf\Stringable\Str;
use Nasustop\HapiBase\Command\GenerateCode\Ast\ModelAddColsVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class GenModel extends AbstractGen
{
    public function createModel(string $bundle, string $table): string
    {
        $option = new ModelOption();
        $option
            ->setPool($this->pool)
            ->setInheritance($this->inheritance)
            ->setUses($this->uses)
            ->setForceCasts(false) // 下面这几个玩意必须加，不然报错，ModelOption没有设置默认值
            ->setRefreshFillable(false)->setWithComments(false)->setWithIde(false);
        $builder = $this->getSchemaBuilder($this->pool);
        // 获取某张表的表结构数组,并将这个结构数组的key转成小写[基本用不到]
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        $project = new Project();
        // singular复数单词转成单数
        // studly将下划线或中划线转成大驼峰
        $class = Str::studly(Str::singular($table)) . 'Model';
        // 根据文件路径获取命名空间
        $class = $project->namespace('src/' . $bundle . '/Model') . $class;
        // 获取绝对路径
        $path = BASE_PATH . '/' . $project->path($class);

        if (! file_exists($path)) {
            $this->mkdir($path);
        } else {
            return $class;
        }
        // 替换模板基础数据并填充到文件中
        $stubs = $this->buildClass($table, $class, $option);
        file_put_contents($path, $stubs);

        // 获取表结构，比之前增加了一个casts字段；时间属性给个默认值datetime
        $columns = $this->getColumns($class, $columns);

        $stms = (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse(file_get_contents($path));
        // 将文件解析到抽象语法树类上面。
        // 应该是用的这个：https://github.com/nikic/PHP-Parser
        // hyperf实现了部分Visitor的代码
        $traverser = new NodeTraverser();
        $traverser->addVisitor(make(ModelUpdateVisitor::class, [
            'class' => $class,
            'columns' => $columns,
            'option' => $option,
        ]));
        $traverser->addVisitor(make(ModelRewriteConnectionVisitor::class, [$class, $option->getPool()]));
        $traverser->addVisitor(make(ModelAddColsVisitor::class, [
            'class' => $class,
            'columns' => $columns,
        ]));
        $data = make(ModelData::class, [$class, $columns])->setClass($class)->setColumns($columns);
        foreach ($option->getVisitors() as $visitorClass) {
            $traverser->addVisitor(make($visitorClass, [$option, $data]));
        }

        $stms = $traverser->traverse($stms);
        $code = (new Standard())->prettyPrintFile($stms);
        file_put_contents($path, $code);

        return $class;
    }

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $table, string $name, ModelOption $option): string
    {
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Model.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceInheritance($stub, $option->getInheritance())
            ->replaceConnection($stub, $option->getPool())
            ->replaceUses($stub, $option->getUses())
            ->replaceClass($stub, $name)
            ->replaceTable($stub, $table);
    }
}
