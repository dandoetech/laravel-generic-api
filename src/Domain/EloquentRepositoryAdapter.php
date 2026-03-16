<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

use DanDoeTech\LaravelResourceRegistry\Contracts\EloquentComputedResolver;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasScope;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Contracts\ComputedFieldDefinitionInterface;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
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
        private readonly ViaResolverFactory $viaResolverFactory,
        private readonly Container $container,
    ) {
    }

    public function paginate(string $resource, array $criteria): array
    {
        $builder = $this->query($resource);
        $res = $this->registry->getResource($resource);

        [$builder, $resolverMap] = $this->applyComputedFields($builder, $res);

        // Filtering: delegate to resolver for computed fields, where() for regular
        // String fields use LIKE for partial matching, others use exact match
        $stringFields = $this->getStringFieldNames($res);
        $stringComputedFields = $this->getStringComputedFieldNames($res);
        foreach (($criteria['filters'] ?? []) as $field => $value) {
            if (isset($resolverMap[$field])) {
                if (\in_array($field, $stringComputedFields, true)) {
                    $builder = $resolverMap[$field]->filter($builder, '%' . $value . '%', 'LIKE');
                } else {
                    $builder = $resolverMap[$field]->filter($builder, $value);
                }
            } elseif (\in_array($field, $stringFields, true)) {
                $builder->where($field, 'LIKE', '%' . $value . '%');
            } else {
                $builder->where($field, $value);
            }
        }

        // Sorting: delegate to resolver for computed fields, orderBy() for regular
        foreach (($criteria['sort'] ?? []) as [$field, $dir]) {
            if (isset($resolverMap[$field])) {
                $builder = $resolverMap[$field]->sort($builder, $dir);
            } else {
                $builder->orderBy($field, $dir);
            }
        }

        $perPage = (int) ($criteria['perPage'] ?? 25);
        $page = (int) ($criteria['page'] ?? 1);

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

        [$builder] = $this->applyComputedFields($builder, $res);

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

    /**
     * @param  Builder<Model>                                                       $builder
     * @return array{0: Builder<Model>, 1: array<string, EloquentComputedResolver>}
     */
    private function applyComputedFields(Builder $builder, ?ResourceDefinitionInterface $res): array
    {
        /** @var array<string, EloquentComputedResolver> $resolverMap */
        $resolverMap = [];
        if ($res !== null) {
            foreach ($res->getComputedFields() as $computed) {
                $resolver = $this->resolveComputed($computed);
                if ($resolver !== null) {
                    $resolverMap[$computed->getName()] = $resolver;
                    $builder = $resolver->apply($builder);
                }
            }
        }

        return [$builder, $resolverMap];
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

    /**
     * @return list<string>
     */
    private function getStringFieldNames(?ResourceDefinitionInterface $res): array
    {
        if ($res === null) {
            return [];
        }

        $names = [];
        foreach ($res->getFields() as $field) {
            if ($field->getType() === FieldType::String) {
                $names[] = $field->getName();
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function getStringComputedFieldNames(?ResourceDefinitionInterface $res): array
    {
        if ($res === null) {
            return [];
        }

        $names = [];
        foreach ($res->getComputedFields() as $computed) {
            if ($computed->getType() === FieldType::String) {
                $names[] = $computed->getName();
            }
        }

        return $names;
    }

    private function resolveComputed(ComputedFieldDefinitionInterface $computed): ?EloquentComputedResolver
    {
        $resolverClass = $computed->getResolver();
        if ($resolverClass !== null) {
            /** @var EloquentComputedResolver */
            return $this->container->make($resolverClass);
        }

        $via = $computed->getVia();
        if ($via !== null) {
            return $this->viaResolverFactory->create($via, $computed->getName());
        }

        return null;
    }
}
