<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain;

interface RepositoryAdapterInterface
{
    /** @return array{data: list<array<string,mixed>>, meta: array<string,mixed>} */
    public function paginate(string $resource, array $criteria): array;

    /** @return array<string,mixed>|null */
    public function find(string $resource, string $id): ?array;

    /** @return array<string,mixed> */
    public function create(string $resource, array $attributes): array;

    /** @return array<string,mixed> */
    public function update(string $resource, string|int $id, array $attributes): array;

    public function delete(string $resource, string|int $id): void;
}
