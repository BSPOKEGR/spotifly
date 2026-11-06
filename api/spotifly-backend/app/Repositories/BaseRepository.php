<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use App\Dto\Filters\FilterData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of Model
 */
class BaseRepository implements RepositoryInterface
{
    /** @var TModel $model */
    protected Model $model;

    /**
     * @param class-string<TModel> $modelClass
     *
     * @throws \Exception
     */
    public function __construct(string $modelClass)
    {
        $this->model = new $modelClass();
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * @return TModel
     */
    public function find(int|string $id): Model
    {
        /** @var TModel $model */
        $model = $this->model->findOrFail($id);

        return $model;
    }

    /**
     * @return TModel
     */
    public function first(string $key, int|float|string $value): Model
    {
        /** @var TModel $model */
        $model = $this->model->where($key, $value)->firstOrFail();

        return $model;
    }

    /**
     * @param array<mixed> $data
     *
     * @return TModel
     */
    public function create(array $data): Model
    {
        /** @var TModel $model */
        $model = $this->model->create($data);

        return $model;
    }

    /**
     * @param array<mixed> $data
     *
     * @return TModel
     */
    public function update(int|string $id, array $data): Model
    {
        $dbModel = $this->find($id);
        $dbModel->update($data);

        return $dbModel;
    }

    /**
     * @param array<mixed> $data
     */
    public function updateModel(Model $model, array $data): Model
    {
        $model->update($data);

        return $model;
    }

    public function delete(int|string $id): ?bool
    {
        $dbModel = $this->find($id);

        return $dbModel->delete();
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function deleteWhere(array $conditions): mixed
    {
        return $this->model->where($conditions)->delete();
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function deleteOneWhere(array $conditions): bool
    {
        $row = $this->model->where($conditions)->first();
        if (null === $row) {
            return false;
        }

        return $row->delete() ?? false;
    }

    /**
     * @param array<string> $collection
     * @param ?array<string, mixed> $conditions
     */
    public function deleteWhereIn(string $key, array $collection, ?array $conditions = null): mixed
    {
        $base = $this->model->whereIn($key, $collection);
        if ($conditions) {
            $base = $base->where($conditions);
        }

        return $base->delete();
    }

    /**
     * @param array<string, mixed> $conditions
     *
     * @return Collection<int, TModel>
     */
    public function where(array $conditions): Collection
    {
        /** @var Collection<int, TModel> $models */
        $models = $this->model->where($conditions)->get();

        return $models;
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions): int
    {
        return $this->model->where($conditions)->count();
    }

    /**
     * @param array<string> $collection
     *
     * @return Collection<int, TModel>
     */
    public function whereKeyIn(string $key, array $collection, ?array $conditions = null): Collection
    {
        $base = $this->model->whereIn($key, $collection);
        if ($conditions) {
            $base = $base->where($conditions);
        }
        /** @var Collection<int, TModel> $models */
        $models = $base->get();

        return $models;
    }

    /**
     * @param array<FilterData> $filters
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function findWithPagination(array $filters, int $pageSize, ?int $page): LengthAwarePaginator
    {
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $this->model::query();

        foreach ($filters as $filter) {
            if ('in' === $filter->operator) {
                $query = $query->whereIn($filter->field, $filter->value);
            } else {
                $query = $query->where($filter->field, $filter->operator, $filter->value);
            }
        }

        $query = $query->orderBy('id', 'asc');

        /** @var LengthAwarePaginator<TModel> $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        return $paginator;
    }

    /**
     * @param array<FilterData> $filters
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function findFilteredWithPagination(
        array $filters,
        int $pageSize,
        ?int $page,
    ): LengthAwarePaginator {
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $this->model::query()
            ->from('users')
            ->select(
                'users.*',
                DB::raw('COALESCE(iud.prefecture_id, cud.prefecture_id) as p_id'),
                'lp.name as prefecture_name',
            )
            ->leftJoin('individual_user_details as iud', 'users.id', '=', 'iud.id')
            ->leftJoin('company_user_details as cud', 'users.id', '=', 'cud.id')
            ->leftJoin('locations_prefectures as lp', function ($join) {
                $join->on(DB::raw('COALESCE(iud.prefecture_id, cud.prefecture_id)'), '=', 'lp.id');
            });

        foreach ($filters as $filter) {
            if ('prefecture_id' === $filter->field) {
                $query = $query->where('iud.prefecture_id', $filter->value)
                    ->orWhere('cud.prefecture_id', $filter->value);
                continue;
            }

            if ('in' === $filter->operator) {
                $query = $query->whereIn($filter->field, $filter->value);
            } else {
                $query = $query->where($filter->field, $filter->operator, $filter->value);
            }
        }

        $query = $query->orderBy('users.id', 'asc');

        /** @var LengthAwarePaginator<TModel> $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        return $paginator;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function updateWhere(array $conditions, array $data): Model
    {
        $dbModel = $this->where($conditions)->first();
        if (null === $dbModel) {
            throw new ModelNotFoundException('Model not found');
        }
        $dbModel->update($data);

        return $dbModel;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function updateForcedWhere(array $conditions, array $data): Model
    {
        $dbModel = $this->where($conditions)->first();
        if (null === $dbModel) {
            throw new ModelNotFoundException('Model not found');
        }
        $dbModel->forceFill($data)->save();

        return $dbModel;
    }

    /**
     * @param array<mixed> $data
     *
     * @return TModel
     */
    public function updateForced(int|string $id, array $data): Model
    {
        $dbModel = $this->find($id);
        $dbModel->forceFill($data)->save();

        return $dbModel;
    }

    /**
     * @param array<int|string> $ids
     */
    public function destroy(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * @param array<string> $fields
     */
    public function getSelectedFields(array $fields): Collection
    {
        return $this->model->select($fields)->get();
    }

    /**
     * @param array<mixed> $data
     */
    public function pluckByKey(string $key, array $data): SupportCollection
    {
        return $this->whereKeyIn($key, $data)->pluck($key);
    }
}
