<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

/**
 * Parses query params into a safe criteria array:
 * - filters: ?filter[name]=abc&filter[category_id]=1
 * - sort:    ?sort=-created_at,name   (minus = desc)
 * - paging:  ?page=1&perPage=50
 */
final class QueryCriteria
{
    /**
     * @param  array<string, mixed>                                                                                         $input
     * @param  list<string>                                                                                                 $filterable
     * @param  list<string>                                                                                                 $sortable
     * @return array{filters: array<string, mixed>, sort: list<array{0: string, 1: 'asc'|'desc'}>, page: int, perPage: int}
     */
    public static function from(array $input, array $filterable, array $sortable, int $defaultPerPage, int $maxPerPage): array
    {
        $filters = [];
        foreach ((array) ($input['filter'] ?? []) as $k => $v) {
            if (\in_array($k, $filterable, true)) {
                $filters[$k] = $v;
            }
        }

        $sort = [];
        $sortInput = $input['sort'] ?? null;
        if (\is_string($sortInput) && $sortInput !== '') {
            $parts = \array_filter(\array_map('trim', \explode(',', $sortInput)));
            foreach ($parts as $p) {
                $dir = \str_starts_with($p, '-') ? 'desc' : 'asc';
                $field = \ltrim($p, '-');
                if (\in_array($field, $sortable, true)) {
                    $sort[] = [$field, $dir];
                }
            }
        }

        $rawPage = $input['page'] ?? 1;
        $rawPerPage = $input['perPage'] ?? $defaultPerPage;
        $page = \max(1, (int) (\is_numeric($rawPage) ? $rawPage : 1));
        $perPage = \max(1, \min((int) (\is_numeric($rawPerPage) ? $rawPerPage : $defaultPerPage), $maxPerPage));

        return \compact('filters', 'sort', 'page', 'perPage');
    }
}
