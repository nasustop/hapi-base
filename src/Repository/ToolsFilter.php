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
            switch (true) {
                case is_int($field) && is_array($filter):
                    // 如果field是int，则filter则必须是数组
                    $query = $this->fieldIntFilter($query, $filter);
                    break;
                case is_string($field):
                    $query = $this->fieldStringFilter($query, $field, $filter);
                    break;
            }
        }
        return $query;
    }

    protected function fieldIntFilter(Builder $query, array $filter): Builder
    {
        $count = count($filter);
        return match (true) {
            $count === 2 && is_string($filter[0]) => $this->fieldStringFilter($query, $filter[0], $filter[1]),
            $count === 3 && is_string($filter[0]) && is_string($filter[1]) => $this->fieldType($query, $filter[0], $filter[1], $filter[2]),
            $count === 3 && is_array($filter[0]) && is_string($filter[1]) && is_array($filter[2]) => $this->fieldGroup($query, $filter[0], $filter[1], $filter[2]),
            default => (function ($query) {
                return $query;
            })($query)
        };
    }

    protected function fieldStringFilter(Builder $query, string $field, $filter): Builder
    {
        $fields = explode('|', $field);
        $count = count($fields);
        switch (true) {
            case $count === 1:
                if (! empty($this->getCols()) && ! in_array($field, $this->getCols())) {
                    return $query;
                }
                // 检测filter是否是数组
                if (is_array($filter)) {
                    $query = $query->whereIn($field, $filter);
                } else {
                    $query = $query->where($field, $filter);
                }
                break;
            case $count === 2 && is_string($fields[0]) && is_string($fields[1]):
                $query = $this->fieldType($query, $fields[0], $fields[1], $filter);
                break;
        }
        return $query;
    }

    protected function fieldGroup(Builder $query, array $field1, string $field2, array $field3): Builder
    {
        // 分组查询
        $field2 = strtoupper($field2);
        return $query->where(function ($query) use ($field1, $field2, $field3) {
            // 分组查询暂时只处理`and`和`or`两种情况
            switch ($field2) {
                case 'AND':
                    // $query
                    $query->where(function ($query) use ($field1) {
                        $this->_filter($query, $field1);
                    })->where(function ($query) use ($field3) {
                        $this->_filter($query, $field3);
                    });
                    break;
                case 'OR':
                    // $query
                    $query->where(function ($query) use ($field1) {
                        $this->_filter($query, $field1);
                    })->orWhere(function ($query) use ($field3) {
                        $this->_filter($query, $field3);
                    });
                    break;
            }
        });
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
