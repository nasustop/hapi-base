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
namespace Nasustop\HapiBase\Template;

abstract class Template
{
    /**
     * 获取API接口地址.
     */
    abstract public function getTableApiUri(): string;

    /**
     * 添加按钮的跳转地址.
     */
    abstract public function getTableHeaderCreateActionUri(): string;

    /**
     * 修改表格某一行的跳转地址.
     */
    abstract public function getTableColumnUpdateActionUri(): string;

    /**
     * 修改表格某一行的跳转地址的参数.
     */
    public function getTableColumnUpdateActionQuery(): array
    {
        return [
            $this->getTableKey() => $this->getTableKey(),
        ];
    }

    /**
     * 删除表格某一行的API接口地址.
     */
    abstract public function getTableColumnDeleteActionUri(): string;

    /**
     * 删除表格某一行的API接口请求方式.
     */
    public function getTableColumnDeleteActionMethod(): string
    {
        return 'post';
    }

    /**
     * 添加表单保存API接口地址.
     * @return string
     */
    abstract public function getCreateFormSaveApiUri(): string;

    /**
     * 修改表单查询基础数据的API接口地址.
     * @return string
     */
    abstract public function getUpdateFormInfoApiUri(): string;

    public function getUpdateFormInfoApiMethod(): string
    {
        return 'get';
    }

    /**
     * 修改表单保存API的接口地址.
     * @return string
     */
    abstract public function getUpdateFormSaveApiUri(): string;

    /**
     * 表格默认页数.
     */
    public function getTableDefaultPage(): int
    {
        return 1;
    }

    /**
     * 表格默认分页.
     */
    public function getTableDefaultPageSize(): int
    {
        return 50;
    }

    /**
     * 表格默认主键.
     */
    abstract public function getTableKey(): string;

    /**
     * 表格头部搜索项.
     */
    abstract public function getTableHeaderFilter(): array;

    /**
     * 表格头部的按钮.
     */
    public function getTableHeaderActions(): array
    {
        return [
            'create' => [
                'title' => '添加',
                'type' => 'primary',
                'icon' => 'el-icon-edit',
                'jump' => true,
                'url' => [
                    'const' => $this->getTableHeaderCreateActionUri(),
                ],
            ],
        ];
    }

    /**
     * 表格字段.
     */
    abstract public function getTableColumns(): array;

    /**
     * 删除表格某一行的API接口参数.
     */
    public function getTableColumnDeleteActionQuery(): array
    {
        return [
            $this->getTableKey() => $this->getTableKey(),
        ];
    }

    /**
     * 获取表格的操作按钮.
     * @return array[]
     */
    public function getTableColumnActions(): array
    {
        return [
            'update' => [
                'title' => '修改',
                'jump' => true,
                'url' => [
                    'const' => $this->getTableColumnUpdateActionUri(),
                    'query' => $this->getTableColumnUpdateActionQuery(),
                ],
            ],
            'delete' => [
                'title' => '删除',
                'type' => 'danger',
                'confirm' => [
                    'title' => '提示',
                    'message' => '确定要删除嘛？',
                    'type' => 'warning',
                ],
                'url' => [
                    'const' => $this->getTableColumnDeleteActionUri(),
                    'method' => $this->getTableColumnDeleteActionMethod(),
                    'query' => $this->getTableColumnDeleteActionQuery(),
                    'notice' => [
                        'success' => [
                            'title' => '删除成功',
                            'message' => '删除成功',
                            'refresh' => true,
                        ],
                        'error' => [
                            'type' => 'warning',
                            'title' => '删除失败',
                            'refresh' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 获取表格模板.
     */
    public function getTableTemplate(): array
    {
        return [
            'type' => 'table',
            'header' => [
                'filter' => $this->getTableHeaderFilter(),
                'actions' => $this->getTableHeaderActions(),
            ],
            'table' => [
                'url' => [
                    'const' => $this->getTableApiUri(),
                ],
                'page' => $this->getTableDefaultPage(),
                'page_size' => $this->getTableDefaultPageSize(),
                'key' => $this->getTableKey(),
                'columns' => $this->getTableColumns(),
                'actions' => $this->getTableColumnActions(),
            ],
        ];
    }

    /**
     * 表单样式.
     */
    public function getCreateFormStyle(): string
    {
        return '';
    }

    /**
     * 表单labelWidth.
     */
    public function getCreateFormLabelWidth(): string
    {
        return '100px';
    }

    abstract public function getCreateFormColumns(): array;

    abstract public function getCreateFormRuleForm(): array;

    abstract public function getCreateFormRules(): array;

    public function getCreateFormActions(): array
    {
        return [
            'cancel' => [
                'title' => '取消',
                'action' => 'rollback',
            ],
            'reset' => [
                'title' => '重置',
                'action' => 'reset',
            ],
            'create' => [
                'title' => '确定',
                'type' => 'primary',
                'action' => 'request',
                'url' => [
                    'const' => $this->getCreateFormSaveApiUri(),
                    'method' => 'post',
                    'hasFilter' => false,
                    'notice' => [
                        'success' => [
                            'title' => '添加成功',
                            'rollback' => true,
                        ],
                        'error' => [
                            'title' => '添加失败',
                            'rollback' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getCreateFormTemplate(): array
    {
        return [
            'type' => 'form',
            'form' => [
                'style' => $this->getCreateFormStyle(),
                'labelWidth' => $this->getCreateFormLabelWidth(),
                'columns' => $this->getCreateFormColumns(),
            ],
            'ruleForm' => $this->getCreateFormRuleForm(),
            'rules' => $this->getCreateFormRules(),
            'actions' => $this->getCreateFormActions(),
        ];
    }

    /**
     * 表单样式.
     */
    public function getUpdateFormStyle(): string
    {
        return '';
    }

    /**
     * 表单labelWidth.
     */
    public function getUpdateFormLabelWidth(): string
    {
        return '100px';
    }

    abstract public function getUpdateFormColumns(): array;

    abstract public function getUpdateFormRuleForm(): array;

    abstract public function getUpdateFormRules(): array;

    public function getUpdateFormActions(): array
    {
        return [
            'cancel' => [
                'title' => '取消',
                'action' => 'rollback',
            ],
            'reset' => [
                'title' => '重置',
                'action' => 'reset',
            ],
            'update' => [
                'title' => '确定',
                'type' => 'primary',
                'action' => 'request',
                'url' => [
                    'const' => $this->getUpdateFormSaveApiUri(),
                    'method' => 'post',
                    'hasFilter' => true,
                    'notice' => [
                        'success' => [
                            'title' => '修改成功',
                            'rollback' => true,
                        ],
                        'error' => [
                            'title' => '修改失败',
                            'rollback' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getUpdateFormTemplate(): array
    {
        return [
            'type' => 'form',
            'form' => [
                'style' => $this->getUpdateFormStyle(),
                'labelWidth' => $this->getUpdateFormLabelWidth(),
                'columns' => $this->getUpdateFormColumns(),
                'uri' => [
                    'const' => $this->getUpdateFormInfoApiUri(),
                    'method' => $this->getUpdateFormInfoApiMethod(),
                ],
            ],
            'ruleForm' => $this->getUpdateFormRuleForm(),
            'rules' => $this->getUpdateFormRules(),
            'actions' => $this->getUpdateFormActions(),
        ];
    }
}
