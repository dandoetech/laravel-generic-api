<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasScope;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class EloquentRepositoryAdapter implements RepositoryAdapterInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly QueryApplier $queryApplier,
        private readonly Container $container,
    ) {
    }

    public function paginate(string $resource, array $criteria): array
    {
        $builder = $this->query($resource);
        $res = $this->registry->getResource($resource);

        /** @var array{filters?: list<array{field: string, operator: string, value: mixed}>, sort?: list<array{0: string, 1: string}>, search?: string|null, page?: int, perPage?: int} $criteria */
        $builder = $this->queryApplier->apply($builder, $res, $criteria);

        $perPage = isset($criteria['perPage']) ? (int) $criteria['perPage'] : 25;
        $page = isset($criteria['page']) ? (int) $criteria['page'] : 1;

        $p = $builder->paginate($perPage, ['*'], 'page', $page);

        /** @var list<array<string, mixed>> $data */
        $data = [];
        foreach ($p->items() as $item) {
            /** @var Model $item */
            /** @var array<string, mixed> $row */
            $row = $item->toArray();
            $data[] = $row;
        }

        /** @var array{data: list<array<string, mixed>>, meta: array<string, mixed>} */
        return [
            'data' => $data,
            'meta' => [
                'page'     => $p->currentPage(),
                'perPage'  => $p->perPage(),
                'total'    => $p->total(),
                'lastPage' => $p->lastPage(),
            ],
        ];
    }

    public function find(string $resource, string $id): ?array
    {
        $builder = $this->query($resource);
        $res = $this->registry->getResource($resource);

        $builder = $this->queryApplier->applyComputedFieldsOnly($builder, $res);

        $m = $builder->find($id);

        /** @var array<string, mixed>|null */
        return $m?->toArray();
    }

    /** @param array<string, mixed> $attributes */
    public function create(string $resource, array $attributes): array
    {
        /** @var array<string, mixed> */
        return DB::transaction(function () use ($resource, $attributes): array {
            $modelClass = $this->model($resource);
            /** @var Model $m */
            $m = new $modelClass();
            $m->fill($attributes);
            $m->save();

            /** @var array<string, mixed> */
            return $m->toArray();
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(string $resource, string|int $id, array $attributes): array
    {
        /** @var array<string, mixed> */
        return DB::transaction(function () use ($resource, $id, $attributes): array {
            /** @var Model $m */
            $m = $this->model($resource)::query()->findOrFail($id);
            $m->fill($attributes);
            $m->save();

            /** @var array<string, mixed> */
            return $m->toArray();
        });
    }

    public function delete(string $resource, string|int $id): void
    {
        DB::transaction(function () use ($resource, $id): void {
            $this->model($resource)::query()->whereKey($id)->delete();
        });
    }

    /** @return Builder<Model> */
    private function query(string $resource): Builder
    {
        $res = $this->resolveResource($resource);
        $builder = $res->model()::query();

        if ($res instanceof HasScope) {
            $scope = $res->scope();
            if ($scope !== null) {
                /** @var \Illuminate\Contracts\Auth\Guard $guard */
                $guard = $this->container->make('auth');
                /** @var Authenticatable|null $user */
                $user = $guard->user();
                if ($scope instanceof \Closure) {
                    /** @var Builder<Model> $builder */
                    $builder = $scope($builder, $user);
                } else {
                    /** @var callable(Builder<Model>, Authenticatable|null): Builder<Model> $instance */
                    $instance = $this->container->make($scope);
                    $builder = $instance($builder, $user);
                }
            }
        }

        /** @var Builder<Model> */
        return $builder;
    }

    /** @return class-string<Model> */
    private function model(string $resource): string
    {
        return $this->resolveResource($resource)->model();
    }

    /** @return HasEloquentModel */
    private function resolveResource(string $resource): HasEloquentModel
    {
        $res = $this->registry->getResource($resource);
        if ($res === null) {
            throw new InvalidArgumentException("Unknown resource '{$resource}'");
        }

        if (!$res instanceof HasEloquentModel) {
            throw new InvalidArgumentException("Resource '{$resource}' does not implement HasEloquentModel");
        }

        return $res;
    }
}
