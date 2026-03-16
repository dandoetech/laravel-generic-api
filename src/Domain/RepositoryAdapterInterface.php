<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

interface RepositoryAdapterInterface
{
    /**
     * @param  array<string, mixed>                                                $criteria
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function paginate(string $resource, array $criteria): array;

    /** @return array<string, mixed>|null */
    public function find(string $resource, string $id): ?array;

    /**
     * @param  array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function create(string $resource, array $attributes): array;

    /**
     * @param  array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function update(string $resource, string|int $id, array $attributes): array;

    public function delete(string $resource, string|int $id): void;
}
