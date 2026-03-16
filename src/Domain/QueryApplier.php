<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

use DanDoeTech\LaravelResourceRegistry\Contracts\EloquentComputedResolver;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Contracts\ComputedFieldDefinitionInterface;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class QueryApplier
{
    public function __construct(
        private readonly ViaResolverFactory $viaResolverFactory,
        private readonly Container $container,
    ) {
    }

    /**
     * Apply computed fields, filters, sorts, and search to a query builder.
     *
     * @param  Builder<Model>                                                                                        $builder
     * @param  ResourceDefinitionInterface|null                                                                      $resource
     * @param  array{filters?: array<string, mixed>, sort?: list<array{0: string, 1: string}>, search?: string|null} $criteria
     * @return Builder<Model>
     */
    public function apply(Builder $builder, ?ResourceDefinitionInterface $resource, array $criteria): Builder
    {
        [$builder, $resolverMap] = $this->applyComputedFields($builder, $resource);

        $builder = $this->applyFilters($builder, $resource, $resolverMap, $criteria['filters'] ?? []);
        $builder = $this->applySearch($builder, $resource, $resolverMap, $criteria['search'] ?? null);
        $builder = $this->applySorts($builder, $resolverMap, $criteria['sort'] ?? []);

        return $builder;
    }

    /**
     * Apply only computed fields (no filters/sorts/search).
     *
     * @param  Builder<Model> $builder
     * @return Builder<Model>
     */
    public function applyComputedFieldsOnly(Builder $builder, ?ResourceDefinitionInterface $resource): Builder
    {
        [$builder] = $this->applyComputedFields($builder, $resource);

        return $builder;
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

    /**
     * @param  Builder<Model>                          $builder
     * @param  array<string, EloquentComputedResolver> $resolverMap
     * @param  array<string, mixed>                    $filters
     * @return Builder<Model>
     */
    private function applyFilters(Builder $builder, ?ResourceDefinitionInterface $res, array $resolverMap, array $filters): Builder
    {
        $stringFields = $this->getStringFieldNames($res);
        $stringComputedFields = $this->getStringComputedFieldNames($res);

        foreach ($filters as $field => $value) {
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

        return $builder;
    }

    /**
     * @param  Builder<Model>                          $builder
     * @param  array<string, EloquentComputedResolver> $resolverMap
     * @return Builder<Model>
     */
    private function applySearch(Builder $builder, ?ResourceDefinitionInterface $res, array $resolverMap, ?string $search): Builder
    {
        if ($search === null || $search === '' || $res === null) {
            return $builder;
        }

        $searchable = $res->getSearchable();
        if ($searchable === []) {
            return $builder;
        }

        $stringFields = $this->getStringFieldNames($res);
        $stringComputedFields = $this->getStringComputedFieldNames($res);

        $builder->where(function (Builder $q) use ($searchable, $search, $resolverMap, $stringFields, $stringComputedFields): void {
            foreach ($searchable as $field) {
                if (isset($resolverMap[$field])) {
                    // Computed field — use resolver filter in OR group
                    if (\in_array($field, $stringComputedFields, true)) {
                        $q->orWhere(fn (Builder $sub) => $resolverMap[$field]->filter($sub, '%' . $search . '%', 'LIKE'));
                    } else {
                        $q->orWhere(fn (Builder $sub) => $resolverMap[$field]->filter($sub, $search));
                    }
                } elseif (\in_array($field, $stringFields, true)) {
                    $q->orWhere($field, 'LIKE', '%' . $search . '%');
                } else {
                    // Non-string field: exact match only
                    $q->orWhere($field, $search);
                }
            }
        });

        return $builder;
    }

    /**
     * @param  Builder<Model>                          $builder
     * @param  array<string, EloquentComputedResolver> $resolverMap
     * @param  list<array{0: string, 1: string}>       $sorts
     * @return Builder<Model>
     */
    private function applySorts(Builder $builder, array $resolverMap, array $sorts): Builder
    {
        foreach ($sorts as [$field, $dir]) {
            if (isset($resolverMap[$field])) {
                $builder = $resolverMap[$field]->sort($builder, $dir);
            } else {
                $builder->orderBy($field, $dir);
            }
        }

        return $builder;
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
