<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\MassAction;

/** DTO for mass action execution. */
final class MassActionRequest
{
    /** @param list<string|int> $ids */
    public function __construct(
        public string $resource,
        public string $action,
        public array $ids,
        /** @var array<string,mixed> */
        public array $payload = [],
    ) {
    }
}
