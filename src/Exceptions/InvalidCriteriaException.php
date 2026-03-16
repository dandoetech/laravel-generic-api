<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Exceptions;

use RuntimeException;

final class InvalidCriteriaException extends RuntimeException
{
    /**
     * @param list<string> $unknownFilters
     * @param list<string> $unknownSorts
     * @param list<string> $allowedFilters
     * @param list<string> $allowedSorts
     */
    public function __construct(
        public readonly array $unknownFilters = [],
        public readonly array $unknownSorts = [],
        public readonly array $allowedFilters = [],
        public readonly array $allowedSorts = [],
    ) {
        $parts = [];
        if ($this->unknownFilters !== []) {
            $parts[] = 'Unknown filter fields: ' . \implode(', ', $this->unknownFilters);
        }
        if ($this->unknownSorts !== []) {
            $parts[] = 'Unknown sort fields: ' . \implode(', ', $this->unknownSorts);
        }

        parent::__construct(\implode('. ', $parts));
    }

    /**
     * @return array<string, list<string>>
     */
    public function toErrors(): array
    {
        $errors = [];
        if ($this->unknownFilters !== []) {
            $errors['filter'] = [
                'Unknown filter fields: ' . \implode(', ', $this->unknownFilters)
                . '. Allowed: ' . \implode(', ', $this->allowedFilters),
            ];
        }
        if ($this->unknownSorts !== []) {
            $errors['sort'] = [
                'Unknown sort fields: ' . \implode(', ', $this->unknownSorts)
                . '. Allowed: ' . \implode(', ', $this->allowedSorts),
            ];
        }

        return $errors;
    }
}
