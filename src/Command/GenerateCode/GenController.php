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

use Hyperf\Database\Connection;
use Hyperf\Database\Query\Processors\MySqlProcessor;
use Hyperf\Utils\CodeGen\Project;
use Hyperf\Utils\Str;

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
        foreach ($columns as $column) {
            if ($column['is_nullable'] == 'YES') {
                continue;
            }
            if ($column['column_key'] == 'PRI') {
                continue;
            }
            $requiredColumns[] = $column['column_name'];
        }
        return $this->putCodeToFile($bundle, $table, $requiredColumns, $type);
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

    protected function putCodeToFile(string $bundle, string $table, array $requiredColumns, string $type = 'Frontend'): string
    {
        $service = $this->genNamespace($bundle, $table, 'Service');
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
        $stubs = $this->buildClass($class, $service);
        $stubs = $this->replaceControllerCreateValidator($stubs, $requiredColumns);
        file_put_contents($path, $stubs);

        return $class;
    }

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $name, string $service): string
    {
        $service_class = str_replace($this->getNamespace($service) . '\\', '', $service);
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Controller.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceUses($stub, $service)
            ->replaceClass($stub, $name)
            ->replaceInjectClass($stub, $service_class);
    }

    protected function replaceControllerCreateValidator($stub, $columns): array|string
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
        $code .= "\t\t\$validator = \$this->validator->make(\$params, \$rules, \$messages);\n\n";
        $code .= "\t\tif (\$validator->fails()) {\n";
        $code .= "\t\t\tthrow new BusinessException(ErrorCode::BAD_REQUEST, \$validator->errors()->first());\n";
        $code .= "\t\t}";
        return str_replace('%CONTROLLER_CREATE_VALIDATOR%', $code, $stub);
    }
}
