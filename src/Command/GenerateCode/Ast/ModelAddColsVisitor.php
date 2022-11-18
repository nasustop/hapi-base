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
namespace Nasustop\HapiBase\Command\GenerateCode\Ast;

use Hyperf\Database\Model\Model;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ModelAddColsVisitor extends NodeVisitorAbstract
{
    protected Model $class;

    protected array $columns = [];

    public function __construct(string $class, array $columns)
    {
        $this->class = new $class();
        $this->columns = $columns;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\PropertyProperty) {
            switch (true) {
                case (string) $node->name === 'cols':
                    $node = $this->rewriteCols($node);
                    break;
                case (string) $node->name === 'primaryKey':
                    $node = $this->rewritePrimaryKey($node);
                    break;
            }
        }
        return $node;
    }

    public function rewritePrimaryKey(Node\Stmt\PropertyProperty $node): Node\Stmt\PropertyProperty
    {
        foreach ($this->columns as $column) {
            if ($column['column_key'] == 'PRI') {
                $node->default = new Node\Scalar\String_($column['column_name']);
            }
        }
        return $node;
    }

    protected function rewriteCols(Node\Stmt\PropertyProperty $node): Node\Stmt\PropertyProperty
    {
        $items = [];
        foreach ($this->columns as $column) {
            $name = $column['column_name'];
            $type = $this->formatDatabaseType($column['data_type']);
            if ($type) {
                $items[] = new Node\Expr\ArrayItem(
//                    new Node\Scalar\String_($type),
                    new Node\Scalar\String_($name)
                );
            }
        }

        $node->default = new Node\Expr\Array_($items, [
            'kind' => Node\Expr\Array_::KIND_SHORT,
        ]);
        return $node;
    }

    protected function formatDatabaseType(string $type): ?string
    {
        switch ($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
            case 'timestamp':
                return 'integer';
            case 'bool':
            case 'boolean':
                return 'boolean';
            case 'varchar':
            case 'enum':
                return 'string';
            case 'json':
                return 'json';
            default:
                return null;
        }
    }
}
