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

trait ToolsFormatColumn
{
    /**
     * 格式化数据.
     */
    public function formatColumnData(array $data): array
    {
        if (empty($this->getCols())) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (! in_array($key, $this->getCols())) {
                continue;
            }
            $result[$key] = $value;
            if (in_array($key, ['created_at', 'updated_at', 'deleted_at'])) {
                $result[$key . '_timestamp'] = strtotime($value);
            }
        }

        return $result;
    }

    public function setColumnData(array $data): array
    {
        if (empty($this->getCols())) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (! in_array($key, $this->getCols())) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    public function fillTimestamp($result)
    {
        if (in_array('created_at', $this->getCols()) && isset($result['created_at'])) {
            $result['created_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('updated_at', $this->getCols()) && isset($result['updated_at'])) {
            $result['updated_at'] = date('Y-m-d H:i:s');
        }

        return $result;
    }

    public function fillUpdateTimestamp($result)
    {
        if (in_array('updated_at', $this->getCols()) && isset($result['updated_at'])) {
            $result['updated_at'] = date('Y-m-d H:i:s');
        }

        return $result;
    }

    /**
     * 格式化columns.
     */
    protected function _columns(array|string $column): array
    {
        if (is_array($column)) {
            return $column;
        }
        $column = explode(',', $column);
        if (is_array($column)) {
            return $column;
        }
        return ['*'];
    }
}
