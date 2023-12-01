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
namespace Nasustop\HapiBase\Repository;

use Hyperf\Database\Model\Builder;

trait ToolsFilter
{
    protected function _filter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $filter) {
            if (is_string($field)) {
                // string下标处理
                $query = $this->fieldStringFilter($query, $field, $filter);
            } elseif (is_int($field) && is_array($filter)) {
                // int下标处理
                $query = $query->where(function ($query) use ($filter) {
                    $this->_filter($query, $filter);
                });
            }
        }
        return $query;
    }

    protected function fieldStringFilter(Builder $query, string $field, $filter): Builder
    {
        $fields = explode('|', $field);
        $count = count($fields);
        if ($count === 2) {
            return $this->fieldType($query, $fields[0], $fields[1], $filter);
        }
        if ($count !== 1) {
            return $query;
        }
        // 下标是数据表字段
        if (! empty($this->getCols()) && in_array($field, $this->getCols())) {
            if (is_array($filter)) {
                $query = $query->whereIn($field, $filter);
            } else {
                $query = $query->where($field, $filter);
            }
            return $query;
        }
        $field = strtoupper($field);
        if ($field == 'AND' && is_array($filter)) {
            $query = $query->where(function ($query) use ($filter) {
                foreach ($filter as $key => $value) {
                    $query = $query->where(function ($query) use ($key, $value) {
                        if (is_string($key)) {
                            $this->fieldStringFilter($query, $key, $value);
                        } elseif (is_int($key) && is_array($value)) {
                            $this->_filter($query, $value);
                        }
                    });
                }
            });
        } elseif ($field == 'OR' && is_array($filter)) {
            $query = $query->where(function ($query) use ($filter) {
                foreach ($filter as $key => $value) {
                    $query = $query->orWhere(function ($query) use ($key, $value) {
                        if (is_string($key)) {
                            $this->fieldStringFilter($query, $key, $value);
                        } elseif (is_int($key) && is_array($value)) {
                            $this->_filter($query, $value);
                        }
                    });
                }
            });
        }
        return $query;
    }

    protected function fieldType(Builder $query, string $field, string $type, $filter): \Hyperf\Database\Query\Builder|Builder
    {
        if (! empty($this->getCols()) && ! in_array($field, $this->getCols())) {
            return $query;
        }
        $type = match ($type) {
            'eq' => '=',
            'neq' => '!=',
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            default => $type,
        };
        return match ($type) {
            'in' => $query->whereIn($field, $filter),
            'notIn' => $query->whereNotIn($field, $filter),
            'between' => $query->whereBetween($field, $filter),
            'notBetween' => $query->whereNotBetween($field, $filter),
            'isNull' => $query->whereNull($field),
            'notNull' => $query->whereNotNull($field),
            'contains' => $query->where($field, 'like', '%' . $filter . '%'),
            default => $query->where($field, $type, $filter),
        };
    }
}
