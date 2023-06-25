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
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Schema\Builder;
use Hyperf\Stringable\Str;

abstract class AbstractGen
{
    /**
     * 数据库链接池.
     */
    protected string $pool = 'default';

    /**
     * 引用类.
     */
    protected string $uses = 'App\Model\Model as BaseModel';

    /**
     * 继承类.
     */
    protected string $inheritance = 'BaseModel';

    /**
     * 获取Builder对象，数据库迁移就是靠这玩意实现的，可以通过它获取表结构，判断表、字段等.
     */
    protected function getSchemaBuilder(string $poolName): Builder
    {
        $resolver = make(ConnectionResolverInterface::class);
        $connection = $resolver->connection($poolName);
        return $connection->getSchemaBuilder();
    }

    /**
     * 将表结构的数组key转成小写，基本用不到.
     */
    protected function formatColumns(array $columns): array
    {
        return array_map(function ($item) {
            return array_change_key_case($item, CASE_LOWER);
        }, $columns);
    }

    /**
     * 创建文件夹.
     */
    protected function mkdir(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * 替换命名空间.
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * 删除类名，获取命名空间.
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * 替换继承类.
     *
     * @return $this
     */
    protected function replaceInheritance(string &$stub, string $inheritance): self
    {
        $stub = str_replace(
            ['%INHERITANCE%'],
            [$inheritance],
            $stub
        );

        return $this;
    }

    /**
     * 替换connection的pool.
     *
     * @return $this
     */
    protected function replaceConnection(string &$stub, string $connection): self
    {
        $stub = str_replace(
            ['%CONNECTION%'],
            [$connection],
            $stub
        );

        return $this;
    }

    /**
     * 替换引用类.
     *
     * @return $this
     */
    protected function replaceUses(string &$stub, string $uses): self
    {
        $uses = $uses ? "use {$uses};" : '';
        $stub = str_replace(
            ['%USES%'],
            [$uses],
            $stub
        );

        return $this;
    }

    /**
     * 替换类名.
     */
    protected function replaceClass(string &$stub, string $name): self
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('%CLASS%', $class, $stub);

        return $this;
    }

    /**
     * 替换AOP类名.
     */
    protected function replaceInjectClass(string &$stub, string $service): string
    {
        $stub = str_replace('%INJECT_CLASS%', $service, $stub);

        return $stub;
    }

    /**
     * 替换service文件的model的primaryKey.
     */
    protected function replacePrimaryKey(string &$stub, string $table): self
    {
        $builder = $this->getSchemaBuilder('default');
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        $primaryKey = '';
        foreach ($columns as $column) {
            if ($column['column_key'] == 'PRI') {
                $primaryKey = $column['column_name'];
            }
        }

        $stub = str_replace('%PRIMARY_KEY%', $primaryKey, $stub);

        return $this;
    }

    /**
     * 替换表名.
     */
    protected function replaceTable(string $stub, string $table): string
    {
        return str_replace('%TABLE%', $table, $stub);
    }

    /**
     * 获取文件的绝对路径.
     */
    protected function getPath(string $name): string
    {
        return BASE_PATH . '/' . str_replace('\\', '/', $name) . '.php';
    }

    protected function getColumns(string $className, array $columns): array
    {
        /** @var Model $model */
        $model = new $className();
        // 获取应该转换成日期的属性
        $dates = $model->getDates();
        // 获取应该强制转换的属性?[比如表结构是bigint，就要转成int]
        $casts = $model->getCasts();

        // 给应该转成日期的属性加一个默认要转成的属性值
        foreach ($dates as $date) {
            if (! isset($casts[$date])) {
                $casts[$date] = 'datetime';
            }
        }

        foreach ($columns as $key => $value) {
            $columns[$key]['cast'] = $casts[$value['column_name']] ?? null;
        }

        return $columns;
    }

    protected function genNamespace(string $bundle, string $table, string $type = 'Model'): string
    {
        if (! in_array($type, ['Model', 'Repository', 'Service'])) {
            throw new \InvalidArgumentException('type类型错误');
        }
        $project = new Project();
        // 获取table的namespace
        $model = Str::studly(Str::singular($table)) . $type;
        return $project->namespace('src/' . $bundle . '/' . $type) . $model;
    }

    protected function getModelNamespace(string $bundle, string $table): string
    {
        $project = new Project();
        // 获取table的namespace
        $model = Str::studly(Str::singular($table)) . 'Model';
        return $project->namespace('src/' . $bundle . '/Model') . $model;
    }

    protected function getRepositoryNamespace(string $bundle, string $table): string
    {
        $project = new Project();
        // 获取table的namespace
        $model = Str::studly(Str::singular($table)) . 'Repository';
        return $project->namespace('src/' . $bundle . '/Repository') . $model;
    }

    protected function getServiceNamespace(string $bundle, string $table): string
    {
        $project = new Project();
        // 获取table的namespace
        $model = Str::studly(Str::singular($table)) . 'Service';
        return $project->namespace('src/' . $bundle . '/Service') . $model;
    }
}
