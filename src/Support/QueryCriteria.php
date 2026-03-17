<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use DanDoeTech\LaravelGenericApi\Exceptions\InvalidCriteriaException;

/**
 * Parses query params into a safe criteria array:
 * - filters: ?filter[name]=abc or ?filter[price][gte]=10
 * - sort:    ?sort=-created_at,name   (minus = desc)
 * - search:  ?search=term
 * - paging:  ?page=1&perPage=50
 *
 * @throws InvalidCriteriaException when unknown filter/sort fields or operators are requested
 */
final class QueryCriteria
{
    private const ALLOWED_OPERATORS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'like', 'between'];

    /**
     * @param  array<string, mixed>                                                                                                                                                    $input
     * @param  list<string>                                                                                                                                                            $filterable
     * @param  list<string>                                                                                                                                                            $sortable
     * @param  list<string>                                                                                                                                                            $stringFields field names that are string type (for auto LIKE)
     * @return array{filters: list<array{field: string, operator: string, value: mixed}>, sort: list<array{0: string, 1: 'asc'|'desc'}>, search: string|null, page: int, perPage: int}
     *
     * @throws InvalidCriteriaException
     */
    public static function from(array $input, array $filterable, array $sortable, int $defaultPerPage, int $maxPerPage, array $stringFields = []): array
    {
        $unknownFilters = [];
        $unknownOperators = [];
        $filters = [];

        foreach ((array) ($input['filter'] ?? []) as $k => $v) {
            if (!\in_array($k, $filterable, true)) {
                $unknownFilters[] = $k;

                continue;
            }

            if (\is_array($v)) {
                // Operator mode: filter[price][gte]=10
                foreach ($v as $op => $val) {
                    if (!\in_array($op, self::ALLOWED_OPERATORS, true)) {
                        $unknownOperators[] = $op;

                        continue;
                    }
                    $filters[] = ['field' => $k, 'operator' => $op, 'value' => $val];
                }
            } else {
                // Legacy mode: filter[field]=value → auto LIKE for strings, exact otherwise
                $op = \in_array($k, $stringFields, true) ? 'like' : 'eq';
                $filters[] = ['field' => $k, 'operator' => $op, 'value' => $v];
            }
        }

        $unknownSorts = [];
        $sort = [];
        $sortInput = $input['sort'] ?? null;
        if (\is_string($sortInput) && $sortInput !== '') {
            $parts = \array_filter(\array_map('trim', \explode(',', $sortInput)));
            foreach ($parts as $p) {
                $dir = \str_starts_with($p, '-') ? 'desc' : 'asc';
                $field = \ltrim($p, '-');
                if (\in_array($field, $sortable, true)) {
                    $sort[] = [$field, $dir];
                } else {
                    $unknownSorts[] = $field;
                }
            }
        }

        if ($unknownFilters !== [] || $unknownSorts !== []) {
            throw new InvalidCriteriaException($unknownFilters, $unknownSorts, $filterable, $sortable);
        }

        if ($unknownOperators !== []) {
            throw new InvalidCriteriaException(
                unknownOperators: \array_values(\array_unique($unknownOperators)),
            );
        }

        $searchInput = $input['search'] ?? null;
        $search = \is_string($searchInput) && $searchInput !== '' ? $searchInput : null;

        $rawPage = $input['page'] ?? 1;
        $rawPerPage = $input['perPage'] ?? $defaultPerPage;
        $page = \max(1, (int) (\is_numeric($rawPage) ? $rawPage : 1));
        $perPage = \max(1, \min((int) (\is_numeric($rawPerPage) ? $rawPerPage : $defaultPerPage), $maxPerPage));

        return \compact('filters', 'sort', 'search', 'page', 'perPage');
    }
}
