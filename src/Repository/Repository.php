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
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;
use Nasustop\HapiBase\Exception\RepositoryRuntimeException;

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
            throw new \LogicException('当前repository必须设置model');
        }
        if (! $this->model instanceof Model) {
            throw new \LogicException('当前repository设置的model类型错误，必须继承基础Model类');
        }
        return $this->model;
    }

    public function getCols(): array
    {
        if (! isset($this->cols)) {
            throw new \LogicException('当前repository必须设置cols');
        }
        return $this->cols;
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
        foreach ($data as &$value) {
            $value = $this->setColumnData($value);
            $data = $this->fillTimestamp($data);
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
     * @throws RepositoryRuntimeException
     */
    public function updateOneBy(array $filter, array $data): array
    {
        $keyName = $this->getModel()->getKeyName();
        if (empty($keyName)) {
            throw new \LogicException('当前Model没有"primaryKey"，请重写updateOneBy方法');
        }
        $result = $this->getInfo($filter);
        if (empty($result)) {
            throw new RepositoryRuntimeException('修改的数据不存在');
        }
        Db::beginTransaction();
        try {
            $rows = $this->findQuery()->find($result[$keyName])->update($data);
            if ($rows > 1) {
                throw new RepositoryRuntimeException('当前条件修改了多条数据');
            }
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollBack();
            throw new RepositoryRuntimeException($exception->getMessage());
        }
        return $this->getInfoByID($result[$keyName]);
    }

    public function deleteBy(array $filter): int
    {
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->delete();
    }

    /**
     * @throws RepositoryRuntimeException
     */
    public function deleteOneBy(array $filter): bool
    {
        $data = $this->getInfo($filter);
        if (empty($data)) {
            throw new RepositoryRuntimeException('删除的数据不存在');
        }
        Db::beginTransaction();
        try {
            $rows = $this->findQuery()->find($data[$this->getModel()->getKeyName()])->delete();
            if ($rows != 1) {
                throw new RepositoryRuntimeException('当前条件删除的数据为' . $rows . '条数据，当前方法只能删除一条数据，请修改条件');
            }
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollBack();
            throw new RepositoryRuntimeException($exception->getMessage());
        }

        return true;
    }

    public function getInfo(array $filter, array|string $columns = '*', array $orderBy = []): array
    {
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

    public function sum(array $filter, string $column)
    {
        $query = $this->findQuery();
        $query = $this->_filter($query, $filter);
        return $query->sum($column);
    }

    public function pageLists(array $filter = [], array|string $columns = '*', int $page = 1, int $pageSize = 100, array $orderBy = []): array
    {
        $count = $this->count($filter);

        $result['total'] = $count;
        $list = $this->getLists($filter, $columns, $page, $pageSize, $orderBy);
        foreach ($list as $key => $value) {
            $list[$key] = $this->formatColumnData($value);
        }
        $result['list'] = $list;
        return $result;
    }
}
