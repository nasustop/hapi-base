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
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\BadRequestHttpException;
use Hyperf\HttpMessage\Exception\ServerErrorHttpException;
use Nasustop\HapiBase\Model\Model;

abstract class Repository implements RepositoryInterface
{
    use ToolsFilter;
    use ToolsFormatColumn;

    /**
     * 获取基础model.
     */
    public function getModel(): Model
    {
        if (empty($this->model)) {
            throw new ServerErrorHttpException('当前repository必须设置model');
        }
        if (! $this->model instanceof Model) {
            throw new ServerErrorHttpException('当前repository设置的model类型错误，必须继承基础Model类');
        }
        return $this->model;
    }

    public function getCols(): array
    {
        return $this->getModel()->getCols();
    }

    public function findQuery(): Builder
    {
        return $this->getModel()->newQuery();
    }

    public function getLastSql(): string
    {
        return $this->findQuery()->toSql();
    }

    public function insert(array $data): bool
    {
        $data = $this->setColumnData($data);
        $data = $this->fillTimestamp($data);

        return $this->findQuery()->insert($data);
    }

    public function insertGetId(array $data): int
    {
        $data = $this->setColumnData($data);
        $data = $this->fillTimestamp($data);

        return $this->findQuery()->insertGetId($data);
    }

    public function batchInsert(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                throw new ServerErrorHttpException('批量添加的数据格式错误');
            }
            $data[$key] = $this->setColumnData($value);
            $data[$key] = $this->fillTimestamp($data[$key]);
        }
        return $this->findQuery()->insert($data);
    }

    public function saveData(array $data): array
    {
        $data = $this->setColumnData($data);
        $data = $this->fillTimestamp($data);

        $id = $this->findQuery()->insertGetId($data);

        return $this->getInfoByID($id);
    }

    public function updateBy(array $filter, array $data): int
    {
        $data = $this->setColumnData($data);
        $data = $this->fillUpdateTimestamp($data);
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->update($data);
    }

    /**
     * @throws BadRequestHttpException
     */
    public function updateOneBy(array $filter, array $data): bool
    {
        $filterCount = $this->count($filter);
        if ($filterCount !== 1) {
            throw new BadRequestHttpException('数据异常，未找到要修改的数据');
        }
        Db::beginTransaction();
        try {
            $data = $this->setColumnData($data);
            $data = $this->fillUpdateTimestamp($data);
            $rows = $this->_filter($this->findQuery(), $filter)->update($data);
            if ($rows > 1) {
                throw new BadRequestHttpException('数据异常，当前方法只允许修改一条数据');
            }
            Db::commit();
        } catch (\Throwable $exception) {
            Db::rollBack();
            throw new BadRequestHttpException($exception->getMessage());
        }
        return true;
    }

    /**
     * 自增.
     */
    public function increment(array $filter, string $column, int|float $number): int
    {
        return $this->_filter($this->findQuery(), $filter)->increment($column, $number);
    }

    /**
     * 自减.
     */
    public function decrement(array $filter, string $column, int|float $number): int
    {
        return $this->_filter($this->findQuery(), $filter)->decrement($column, $number);
    }

    public function deleteBy(array $filter): int
    {
        if (in_array('deleted_at', $this->getCols())) {
            return $this->updateBy($filter, ['deleted_at' => date('Y-m-d H:i:s')]);
        }
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->delete();
    }

    /**
     * @throws BadRequestHttpException
     */
    public function deleteOneBy(array $filter): bool
    {
        if (in_array('deleted_at', $this->getCols())) {
            return $this->updateOneBy($filter, ['deleted_at' => date('Y-m-d H:i:s')]);
        }
        $filterCount = $this->count($filter);
        if ($filterCount !== 1) {
            throw new BadRequestHttpException('数据异常，未找到要删除的数据');
        }
        Db::beginTransaction();
        try {
            $rows = $this->_filter($this->findQuery(), $filter)->delete();
            if ($rows > 1) {
                throw new BadRequestHttpException('数据异常，当前方法只允许删除一条数据');
            }
            Db::commit();
        } catch (\Throwable $exception) {
            Db::rollBack();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return true;
    }

    public function getInfo(array $filter, array|string $columns = '*', array $orderBy = []): array
    {
        if (in_array('deleted_at', $this->getCols()) && ! in_array('deleted_at', $filter)) {
            $filter['deleted_at'] = null;
        }
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        $columns = $this->_columns($columns);
        $query = $query->select($columns);
        foreach ($orderBy as $key => $value) {
            $query = $query->orderBy($key, $value);
        }
        $result = $query->first();
        $result = $result ? $result->toArray() : [];
        return $this->formatColumnData($result);
    }

    public function getInfoByID(mixed $primary_key_id): array
    {
        $result = $this->findQuery()->find($primary_key_id);
        $result = $result ? $result->toArray() : [];
        return $this->formatColumnData($result);
    }

    public function getLists(array $filter = [], array|string $columns = '*', int $page = 0, int $pageSize = 0, array $orderBy = []): array
    {
        if (in_array('deleted_at', $this->getCols()) && ! in_array('deleted_at', $filter)) {
            $filter['deleted_at'] = null;
        }
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        $columns = $this->_columns($columns);
        $query = $query->select($columns);
        if ($page > 0 && $pageSize > 0) {
            $query = $query->offset(($page - 1) * $pageSize)
                ->limit($pageSize);
        }
        foreach ($orderBy as $key => $value) {
            $query = $query->orderBy($key, $value);
        }
        $result = $query->get()->toArray();
        foreach ($result as $key => $value) {
            $result[$key] = $this->formatColumnData($value);
        }
        return $result;
    }

    public function count(array $filter): int
    {
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->count();
    }

    public function sum(array $filter, string $column): int
    {
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->sum($column);
    }

    public function pageLists(array $filter = [], array|string $columns = '*', int $page = 1, int $pageSize = 100, array $orderBy = []): array
    {
        if (in_array('deleted_at', $this->getCols()) && ! in_array('deleted_at', $filter)) {
            $filter['deleted_at'] = null;
        }
        $count = $this->count($filter);

        $result['total'] = $count;
        $result['list'] = $this->getLists($filter, $columns, $page, $pageSize, $orderBy);
        return $result;
    }

    public function beginTransaction()
    {
        Db::beginTransaction();
    }

    public function rollBack()
    {
        Db::rollBack();
    }

    public function commit()
    {
        Db::commit();
    }

    public function transaction(\Closure $callback, int $attempts = 1)
    {
        Db::transaction($callback, $attempts);
    }

    public function rawSelect(string $query): array
    {
        return Db::select($query);
    }

    public function rawSelectOne(string $query): array
    {
        return Db::selectOne($query);
    }

    public function rawInsert(string $query): bool
    {
        return Db::insert($query);
    }

    public function rawUpdate(string $query): int
    {
        return Db::update($query);
    }

    public function rawDelete(string $query): int
    {
        return Db::delete($query);
    }
}
