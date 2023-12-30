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
use Hyperf\Database\Connection;
use Hyperf\Database\Query\Processors\MySqlProcessor;
use Hyperf\Stringable\Str;

class GenController extends AbstractGen
{
    public function createController(string $bundle, string $table, $type = 'Frontend'): string
    {
        if (! in_array($type, ['Frontend', 'Backend'])) {
            throw new \InvalidArgumentException('生成controller文件类型错误');
        }
        $connection = $this->getSchemaBuilder($this->pool)->getConnection();
        $columns = $this->getColumnTypeListing($connection, $table);
        $requiredColumns = [];
        $priKey = '';
        $enumColumns = [];
        foreach ($columns as $column) {
            if ($column['is_nullable'] == 'YES') {
                continue;
            }
            if ($column['column_key'] == 'PRI') {
                $priKey = $column['column_name'];
                continue;
            }
            if ($column['data_type'] == 'enum') {
                $enum_string = ltrim(rtrim($column['column_type'], ')'), 'enum(');
                $enum_data = explode(',', $enum_string);
                array_walk($enum_data, function (&$value) {
                    $value = trim($value, "'");
                });
                $enumColumns[$column['column_name']] = $enum_data;
            }
            $requiredColumns[] = $column['column_name'];
        }
        return $this->putCodeToFile($bundle, $table, $priKey, $requiredColumns, $enumColumns, $type);
    }

    /**
     * Compile the query to determine the list of columns.
     */
    public function compileColumnListing(): string
    {
        return 'select `column_key` as `column_key`, `column_name` as `column_name`, `data_type` as `data_type`, `column_comment` as `column_comment`, `extra` as `extra`, `column_type` as `column_type`, `is_nullable` as `is_nullable` from information_schema.columns where `table_schema` = ? and `table_name` = ? order by ORDINAL_POSITION';
    }

    /**
     * Get the column type listing for a given table.
     */
    public function getColumnTypeListing(Connection $connection, string $table): array
    {
        $table = $connection->getTablePrefix() . $table;

        $results = $connection->select(
            $this->compileColumnListing(),
            [$connection->getDatabaseName(), $table]
        );

        /** @var MySqlProcessor $processor */
        $processor = $connection->getPostProcessor();
        return $processor->processListing($results);
    }

    protected function putCodeToFile(string $bundle, string $table, string $priKey, array $requiredColumns, array $enumColumns, string $type = 'Frontend'): string
    {
        $service = $this->genNamespace($bundle, $table, 'Service');
        $template = $this->genNamespace($bundle, $table, 'Template');
        $project = new Project();
        // singular复数单词转成单数
        // studly将下划线或中划线转成大驼峰
        $class = Str::studly(Str::singular($table)) . 'Controller';
        // 根据文件路径获取命名空间
        $class = $project->namespace('src/' . $bundle . '/Controller/' . $type) . $class;
        // 获取绝对路径
        $path = BASE_PATH . '/' . $project->path($class);

        if (! file_exists($path)) {
            $this->mkdir($path);
        }
        // 替换模板基础数据并填充到文件中
        $stubs = $this->buildClass($class, $service, $template);
        $stubs = $this->replaceControllerCreateValidator($stubs, $requiredColumns);
        $stubs = $this->replaceControllerUpdateValidator($stubs, $priKey, $requiredColumns);
        $stubs = $this->replaceControllerEnumAction($stubs, $enumColumns);

        if (! is_file($path)) {
            file_put_contents($path, $stubs);
        }

        return $class;
    }

    /**
     * 替换AOP类名.
     */
    protected function replaceTemplateClass(string &$stub, string $template): self
    {
        $stub = str_replace('%TEMPLATE_CLASS%', $template, $stub);

        return $this;
    }

    /**
     * 替换引用类.
     *
     * @return $this
     */
    protected function replaceUses(string &$stub, string $uses): self
    {
        $stub = str_replace(
            ['%USES%'],
            [$uses],
            $stub
        );

        return $this;
    }

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $name, string $service, string $template): string
    {
        $service_class = str_replace($this->getNamespace($service) . '\\', '', $service);
        $template_class = str_replace($this->getNamespace($template) . '\\', '', $template);
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Controller.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceUses($stub, "use {$service};\nuse {$template};")
            ->replaceClass($stub, $name)
            ->replaceTemplateClass($stub, $template_class)
            ->replaceInjectClass($stub, $service_class);
    }

    protected function replaceControllerCreateValidator(string $stub, array $columns): array|string
    {
        if (empty($columns)) {
            return str_replace('%CONTROLLER_CREATE_VALIDATOR%', '', $stub);
        }
        $code = "\$rules = [\n";
        foreach ($columns as $column) {
            $code .= "\t\t\t'{$column}' => 'required',\n";
        }
        $code .= "\t\t];\n";
        $code .= "\t\t\$messages = [\n";
        foreach ($columns as $column) {
            $code .= "\t\t\t'{$column}.required' => '{$column} 参数必填',\n";
        }
        $code .= "\t\t];\n";
        $code .= "\t\t\$validator = \$this->getValidatorFactory()->make(data: \$params, rules: \$rules, messages: \$messages);\n\n";
        $code .= "\t\tif (\$validator->fails()) {\n";
        $code .= "\t\t\tthrow new BadRequestHttpException(message: \$validator->errors()->first());\n";
        $code .= "\t\t}";
        return str_replace('%CONTROLLER_CREATE_VALIDATOR%', $code, $stub);
    }

    protected function replaceControllerUpdateValidator(string $stub, string $priKey, array $columns): array|string
    {
        if (empty($columns)) {
            return str_replace('%CONTROLLER_UPDATE_VALIDATOR%', '', $stub);
        }
        $code = "\$rules = [\n";
        $code .= "\t\t\t'filter' => 'required|array',\n";
        $code .= "\t\t\t'filter.{$priKey}' => 'required',\n";
        $code .= "\t\t\t'params' => 'required|array',\n";
        foreach ($columns as $column) {
            $code .= "\t\t\t'params.{$column}' => 'required',\n";
        }
        $code .= "\t\t];\n";
        $code .= "\t\t\$messages = [\n";
        $code .= "\t\t\t'filter.required' => 'filter 参数必填',\n";
        $code .= "\t\t\t'filter.array' => 'filter 参数错误，必须是数组格式',\n";
        $code .= "\t\t\t'filter.{$priKey}.required' => 'filter.{$priKey} 参数必填',\n";
        $code .= "\t\t\t'params.required' => 'params 参数必填',\n";
        $code .= "\t\t\t'params.array' => 'params 参数错误，必须是数组格式',\n";
        foreach ($columns as $column) {
            $code .= "\t\t\t'params.{$column}.required' => 'params.{$column} 参数必填',\n";
        }
        $code .= "\t\t];\n";
        $code .= "\t\t\$validator = \$this->getValidatorFactory()->make(data: \$params, rules: \$rules, messages: \$messages);\n\n";
        $code .= "\t\tif (\$validator->fails()) {\n";
        $code .= "\t\t\tthrow new BadRequestHttpException(message: \$validator->errors()->first());\n";
        $code .= "\t\t}";
        return str_replace('%CONTROLLER_UPDATE_VALIDATOR%', $code, $stub);
    }

    protected function replaceControllerEnumAction(string $stub, array $enumColumns): array|string
    {
        $code = "\n";
        // 获取基础内容
        $enum_stub = file_get_contents(__DIR__ . '/stubs/ControllerEnum.stub');
        foreach ($enumColumns as $name => $value) {
            $enum_stub_str = $enum_stub;
            $column_name = 'ENUM_' . strtoupper($name);
            $actionName = $this->convertUnderline('action_' . strtolower($column_name), false);
            $functionColumn = $this->convertUnderline(strtolower($column_name), false);
            $functionColumnDefault = $this->convertUnderline(strtolower("{$column_name}_DEFAULT"), false);
            $enum_stub_str = str_replace('%ENUM_ACTION_NAME%', $actionName, $enum_stub_str);
            $enum_stub_str = str_replace('%ENUM_NAME%', $functionColumn, $enum_stub_str);
            $enum_stub_str = str_replace('%ENUM_DEFAULT_NAME%', $functionColumnDefault, $enum_stub_str);
            $code .= $enum_stub_str;
        }
        return str_replace('%CONTROLLER_ENUM_ACTION%', $code, $stub);
    }

    protected function convertUnderline($str, $ucfirst = true): array|string
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', lcfirst($str));
        return $ucfirst ? ucfirst($str) : $str;
    }
}
