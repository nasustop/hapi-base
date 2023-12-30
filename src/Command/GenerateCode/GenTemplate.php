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

class GenTemplate extends AbstractGen
{
    public function createTemplate(string $bundle, string $table): string
    {
        $connection = $this->getSchemaBuilder($this->pool)->getConnection();
        $columns = $this->getColumnTypeListing($connection, $table);

        $project = new Project();
        // singular复数单词转成单数
        // studly将下划线或中划线转成大驼峰
        $class = Str::studly(Str::singular($table)) . 'Template';
        // 根据文件路径获取命名空间
        $class = $project->namespace('src/' . $bundle . '/Template') . $class;
        // 获取绝对路径
        $path = BASE_PATH . '/' . $project->path($class);

        if (! file_exists($path)) {
            $this->mkdir($path);
        }
        // 替换模板基础数据并填充到文件中
        $stubs = $this->buildClass($class);

        $priKey = '';
        $tableHeaderFilter = '';
        $tableColumns = '';
        $formCreateColumns = '';
        $formCreateRuleForm = '';
        $formCreateRules = '';
        $formUpdateColumns = '';
        $formUpdateRuleForm = '';
        $formUpdateRules = '';
        foreach ($columns as $column) {
            if ($column['column_key'] === 'PRI') {
                $priKey = $column['column_name'];
                continue;
            }
            if ($column['column_type'] == 'timestamp') {
                continue;
            }
            // tableHeaderFilter
            $tableHeaderFilter .= "\t\t\t'{$column['column_name']}' => [\n";
            $tableHeaderFilter .= "\t\t\t\t'placeholder' => '请输入{$column['column_comment']}',\n";
            $tableHeaderFilter .= "\t\t\t\t'clearable' => true,\n";
            $tableHeaderFilter .= "\t\t\t],\n";
            // tableColumns
            $tableColumns .= "\t\t\t'{$column['column_name']}' => [\n";
            $tableColumns .= "\t\t\t\t'title' => '{$column['column_comment']}',\n";
            $tableColumns .= "\t\t\t],\n";
            // formCreateColumns
            $formCreateColumns .= "\t\t\t'{$column['column_name']}' => [\n";
            $formCreateColumns .= "\t\t\t\t'title' => '{$column['column_comment']}',\n";
            $formCreateColumns .= "\t\t\t\t'type' => 'text',\n";
            $formCreateColumns .= "\t\t\t],\n";
            // formUpdateColumns
            $formUpdateColumns .= "\t\t\t'{$column['column_name']}' => [\n";
            $formUpdateColumns .= "\t\t\t\t'title' => '{$column['column_comment']}',\n";
            $formUpdateColumns .= "\t\t\t\t'type' => 'text',\n";
            $formUpdateColumns .= "\t\t\t],\n";
            // formCreateRuleForm
            $formCreateRuleForm .= "\t\t\t'{$column['column_name']}' => '',\n";
            // formUpdateRuleForm
            $formUpdateRuleForm .= "\t\t\t'{$column['column_name']}' => '',\n";
            if ($column['is_nullable'] == 'NO') {
                // formCreateRules
                $formCreateRules .= "\t\t\t'{$column['column_name']}' => [\n";
                $formCreateRules .= "\t\t\t\t[\n";
                $formCreateRules .= "\t\t\t\t\t'required' => true,\n";
                $formCreateRules .= "\t\t\t\t\t'message' => '{$column['column_comment']}必填',\n";
                $formCreateRules .= "\t\t\t\t\t'trigger' => 'change',\n";
                $formCreateRules .= "\t\t\t\t],\n";
                $formCreateRules .= "\t\t\t],\n";
                // formUpdateRules
                $formUpdateRules .= "\t\t\t'{$column['column_name']}' => [\n";
                $formUpdateRules .= "\t\t\t\t[\n";
                $formUpdateRules .= "\t\t\t\t\t'required' => true,\n";
                $formUpdateRules .= "\t\t\t\t\t'message' => '{$column['column_comment']}必填',\n";
                $formUpdateRules .= "\t\t\t\t\t'trigger' => 'change',\n";
                $formUpdateRules .= "\t\t\t\t],\n";
                $formUpdateRules .= "\t\t\t],\n";
            }
        }
        $stubs = str_replace('%TABLE_KEY%', $priKey, $stubs);
        // tableHeaderFilter
        if (! empty($tableHeaderFilter)) {
            $tableHeaderFilter = "\n" . $tableHeaderFilter . "\t\t";
        }
        $stubs = str_replace('%TABLE_HEADER_FILTER%', $tableHeaderFilter, $stubs);
        // tableColumns
        if (! empty($tableColumns)) {
            $tableColumns = "\n" . $tableColumns . "\t\t";
        }
        $stubs = str_replace('%TABLE_COLUMNS%', $tableColumns, $stubs);
        // formCreateColumns
        if (! empty($formCreateColumns)) {
            $formCreateColumns = "\n" . $formCreateColumns . "\t\t";
        }
        $stubs = str_replace('%FORM_CREATE_COLUMNS%', $formCreateColumns, $stubs);
        // formCreateRuleForm
        if (! empty($formCreateRuleForm)) {
            $formCreateRuleForm = "\n" . $formCreateRuleForm . "\t\t";
        }
        $stubs = str_replace('%FORM_CREATE_RULE_FORM%', $formCreateRuleForm, $stubs);
        // formCreateRules
        if (! empty($formCreateRules)) {
            $formCreateRules = "\n" . $formCreateRules . "\t\t";
        }
        $stubs = str_replace('%FORM_CREATE_RULES%', $formCreateRules, $stubs);
        // formUpdateColumns
        if (! empty($formUpdateColumns)) {
            $formUpdateColumns = "\n" . $formUpdateColumns . "\t\t";
        }
        $stubs = str_replace('%FORM_UPDATE_COLUMNS%', $formUpdateColumns, $stubs);
        // formUpdateRuleForm
        if (! empty($formUpdateRuleForm)) {
            $formUpdateRuleForm = "\n" . $formUpdateRuleForm . "\t\t";
        }
        $stubs = str_replace('%FORM_UPDATE_RULE_FORM%', $formUpdateRuleForm, $stubs);
        // formUpdateRules
        if (! empty($formUpdateRules)) {
            $formUpdateRules = "\n" . $formUpdateRules . "\t\t";
        }
        $stubs = str_replace('%FORM_UPDATE_RULES%', $formUpdateRules, $stubs);

        //        if (! is_file($path)) {
                    file_put_contents($path, $stubs);
        //        }

        return $class;
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

    /**
     * 根据表名和类名替换基础代码模版里的内容.
     */
    protected function buildClass(string $name): string
    {
        // 获取基础内容
        $stub = file_get_contents(__DIR__ . '/stubs/Template.stub');

        $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);

        return $stub;
    }
}
