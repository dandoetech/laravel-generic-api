<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class EloquentRepositoryAdapter implements RepositoryAdapterInterface
{
    /** @param array<string, class-string<Model>> $resourceToModel */
    public function __construct(private readonly array $resourceToModel)
    {
    }

    public function paginate(string $resource, array $criteria): array
    {
        $builder = $this->query($resource);

        // filtering
        foreach (($criteria['filters'] ?? []) as $field => $value) {
            $builder->where($field, $value);
        }

        // sorting
        foreach (($criteria['sort'] ?? []) as [$field, $dir]) {
            $builder->orderBy($field, $dir);
        }

        $perPage = (int)($criteria['perPage'] ?? 25);
        $page = (int)($criteria['page'] ?? 1);

        /** @var LengthAwarePaginator $p */
        $p = $builder->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(fn ($m) => $m->toArray(), $p->items()),
            'meta' => [
                'page' => $p->currentPage(),
                'perPage' => $p->perPage(),
                'total' => $p->total(),
                'lastPage' => $p->lastPage(),
            ],
        ];
    }

    public function find(string $resource, string $id): ?array
    {
        $m = $this->model($resource)::query()->find($id);
        return $m?->toArray();
    }

    public function create(string $resource, array $attributes): array
    {
        $modelClass = $this->model($resource);
        /** @var Model $m */
        $m = new $modelClass();
        $m->fill($attributes);
        $m->save();
        return $m->toArray();
    }

    public function update(string $resource, string|int $id, array $attributes): array
    {
        /** @var Model $m */
        $m = $this->model($resource)::query()->findOrFail($id);
        $m->fill($attributes);
        $m->save();
        return $m->toArray();
    }

    public function delete(string $resource, string|int $id): void
    {
        $this->model($resource)::query()->whereKey($id)->delete();
    }

    /** @return Builder<Model> */
    private function query(string $resource): Builder
    {
        return $this->model($resource)::query();
    }

    /** @return class-string<Model> */
    private function model(string $resource): string
    {
        $class = $this->resourceToModel[$resource] ?? null;
        if (!$class) {
            throw new InvalidArgumentException("No model mapped for resource '{$resource}'");
        }
        return $class;
    }
}
