<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\Actions;

use Illuminate\Contracts\Auth\Authenticatable;

interface ActionHandlerInterface
{
    /**
     * Execute a custom action on one or more records.
     *
     * @param list<string|int>     $ids
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function handle(string $resource, array $ids, array $payload, ?Authenticatable $user): array;
}
