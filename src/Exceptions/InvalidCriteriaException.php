<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Exceptions;

use RuntimeException;

final class InvalidCriteriaException extends RuntimeException
{
    private const ALLOWED_OPERATORS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'like', 'between'];

    /**
     * @param list<string> $unknownFilters
     * @param list<string> $unknownSorts
     * @param list<string> $allowedFilters
     * @param list<string> $allowedSorts
     * @param list<string> $unknownOperators
     */
    public function __construct(
        public readonly array $unknownFilters = [],
        public readonly array $unknownSorts = [],
        public readonly array $allowedFilters = [],
        public readonly array $allowedSorts = [],
        public readonly array $unknownOperators = [],
    ) {
        $parts = [];
        if ($this->unknownFilters !== []) {
            $parts[] = 'Unknown filter fields: ' . \implode(', ', $this->unknownFilters);
        }
        if ($this->unknownSorts !== []) {
            $parts[] = 'Unknown sort fields: ' . \implode(', ', $this->unknownSorts);
        }
        if ($this->unknownOperators !== []) {
            $parts[] = 'Unknown operators: ' . \implode(', ', $this->unknownOperators);
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
        if ($this->unknownOperators !== []) {
            $errors['operator'] = [
                'Unknown operators: ' . \implode(', ', $this->unknownOperators)
                . '. Allowed: ' . \implode(', ', self::ALLOWED_OPERATORS),
            ];
        }

        return $errors;
    }
}
