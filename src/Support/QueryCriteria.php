<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use DanDoeTech\LaravelGenericApi\Exceptions\InvalidCriteriaException;

/**
 * Parses query params into a safe criteria array:
 * - filters: ?filter[name]=abc&filter[category_id]=1
 * - sort:    ?sort=-created_at,name   (minus = desc)
 * - paging:  ?page=1&perPage=50
 *
 * @throws InvalidCriteriaException when unknown filter/sort fields are requested
 */
final class QueryCriteria
{
    /**
     * @param  array<string, mixed>                                                                                                              $input
     * @param  list<string>                                                                                                                      $filterable
     * @param  list<string>                                                                                                                      $sortable
     * @return array{filters: array<string, mixed>, sort: list<array{0: string, 1: 'asc'|'desc'}>, search: string|null, page: int, perPage: int}
     *
     * @throws InvalidCriteriaException
     */
    public static function from(array $input, array $filterable, array $sortable, int $defaultPerPage, int $maxPerPage): array
    {
        $unknownFilters = [];
        $filters = [];
        foreach ((array) ($input['filter'] ?? []) as $k => $v) {
            if (\in_array($k, $filterable, true)) {
                $filters[$k] = $v;
            } else {
                $unknownFilters[] = $k;
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

        $searchInput = $input['search'] ?? null;
        $search = \is_string($searchInput) && $searchInput !== '' ? $searchInput : null;

        $rawPage = $input['page'] ?? 1;
        $rawPerPage = $input['perPage'] ?? $defaultPerPage;
        $page = \max(1, (int) (\is_numeric($rawPage) ? $rawPage : 1));
        $perPage = \max(1, \min((int) (\is_numeric($rawPerPage) ? $rawPerPage : $defaultPerPage), $maxPerPage));

        return \compact('filters', 'sort', 'search', 'page', 'perPage');
    }
}
