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

interface RepositoryInterface
{
    public function getModel(): Model;

    public function getCols(): array;

    public function findQuery(): Builder;

    public function getLastSql(): string;

    public function insert(array $data): bool;

    public function batchInsert(array $data): bool;

    public function saveData(array $data): array;

    public function updateBy(array $filter, array $data): int;

    public function updateOneBy(array $filter, array $data): array;

    public function deleteBy(array $filter): int;

    public function deleteOneBy(array $filter): bool;

    public function getInfo(array $filter, array|string $columns, array $orderBy): array;

    public function getLists(array $filter, array|string $columns, int $page, int $pageSize, array $orderBy): array;

    public function count(array $filter): int;

    public function pageLists(array $filter, array|string $columns, int $page, int $pageSize, array $orderBy): array;
}
