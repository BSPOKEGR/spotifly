<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Dto\Filters\FilterData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface RepositoryInterface
{
    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    public function find(int $id): Model;

    public function first(string $key, int|float|string $value): Model;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Model;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Model;

    /**
     * @param array<mixed> $data
     */
    public function updateModel(Model $model, array $data): Model;

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $data
     */
    public function updateWhere(array $conditions, array $data): Model;

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $data
     */
    public function updateForcedWhere(array $conditions, array $data): Model;

    public function delete(int $id): ?bool;

    /**
     * @param array<int|string> $ids
     */
    public function destroy(array $ids): int;

    /**
     * @param array<string, mixed> $conditions
     */
    public function deleteWhere(array $conditions): mixed;

    /**
     * @param array<string, mixed> $conditions
     */
    public function deleteOneWhere(array $conditions): bool;

    /**
     * @param array<string, mixed> $collection
     * @param ?array<string, mixed> $conditions
     */
    public function deleteWhereIn(string $key, array $collection, ?array $conditions = null): mixed;

    /**
     * @param array<string, mixed> $conditions
     *
     * @return Collection<int, Model>
     */
    public function where(array $conditions): Collection;

    /**
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions): int;

    /**
     * @param array<string, mixed> $collection
     * @param array<string, mixed> $conditions

     * @return Collection<int, Model>
     */
    public function whereKeyIn(string $key, array $collection, ?array $conditions = null): Collection;

    /**
     * @param array<FilterData> $filters
     *
     * @return LengthAwarePaginator<Model>
     */
    public function findWithPagination(array $filters, int $pageSize, ?int $page): LengthAwarePaginator;

    /**
     * @param array<FilterData> $filters
     *
     * @return LengthAwarePaginator<Model>
     */
    public function findFilteredWithPagination(array $filters, int $pageSize, ?int $page): LengthAwarePaginator;

    /**
     * @param array<mixed> $data
     */
    public function updateForced(int $id, array $data): Model;

    /**
     * @param array<mixed> $fields
     *
     * @return Collection<int, Model>
     */
    public function getSelectedFields(array $fields): Collection;

    /**
     * @param array<mixed> $data
     *
     * @return Collection<int, Model>
     */
    public function pluckByKey(string $key, array $data): SupportCollection;
}
