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
use Hyperf\Stringable\Str;

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

        $builder = $this->getSchemaBuilder($this->pool);
        // 获取某张表的表结构数组,并将这个结构数组的key转成小写[基本用不到]
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        if (is_file($path)) {
            $stubs = str_replace('%REPOSITORY_ENUM%', '', $stubs);
        } else {
            $stubs = $this->replaceConstEnum($columns, $stubs);
        }
        if (! is_file($path)) {
            file_put_contents($path, $stubs);
        }

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

    protected function replaceConstEnum(array $columns, string $stubs): array|string
    {
        $enumColumns = [];
        foreach ($columns as $column) {
            if ($column['data_type'] == 'enum') {
                $enum_string = ltrim(rtrim($column['column_type'], ')'), 'enum(');
                $enum_data = explode(',', $enum_string);
                array_walk($enum_data, function (&$value) {
                    $value = trim($value, "'");
                });
                $enumColumns[$column['column_name']] = $enum_data;
            }
        }
        $code = "\n";
        foreach ($enumColumns as $name => $value) {
            // 获取基础内容
            $enum_stub = file_get_contents(__DIR__ . '/stubs/RepositoryEnum.stub');
            $column_name = 'ENUM_' . strtoupper($name);
            $column_enum_str = '';
            $default_column = '';
            foreach ($value as $vv) {
                $vv_name = $column_name . '_' . strtoupper($vv);
                if (empty($default_column)) {
                    $default_column = $vv_name;
                }
                $code .= "\tpublic const {$vv_name} = '{$vv}';\n";
                $column_enum_str .= "self::{$vv_name} => '{$vv}',";
            }
            $column_enum_str = rtrim($column_enum_str, ',');
            $code .= "\tpublic const {$column_name} = [{$column_enum_str}];\n";
            $code .= "\tpublic const {$column_name}_DEFAULT = self::{$default_column};\n";
            $functionColumn = $this->convertUnderline(strtolower($column_name), false);
            $functionColumnDefault = $this->convertUnderline(strtolower("{$column_name}_DEFAULT"), false);
            $enum_stub = str_replace('%ENUM_NAME%', $functionColumn, $enum_stub);
            $enum_stub = str_replace('%ENUM_CONST_NAME%', $column_name, $enum_stub);
            $enum_stub = str_replace('%ENUM_DEFAULT_NAME%', $functionColumnDefault, $enum_stub);
            $enum_stub = str_replace('%ENUM_DEFAULT_CONST_NAME%', "{$column_name}_DEFAULT", $enum_stub);
            $code .= $enum_stub;
        }
        return str_replace('%REPOSITORY_ENUM%', $code, $stubs);
    }

    protected function convertUnderline($str, $ucfirst = true): array|string
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', lcfirst($str));
        return $ucfirst ? ucfirst($str) : $str;
    }
}
