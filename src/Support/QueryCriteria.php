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
     * @param list<string> $filterable
     * @param list<string> $sortable
     * @return array{filters: array<string,mixed>, sort: list<array{0:string,1:'asc'|'desc'}>, page:int, perPage:int}
     */
    public static function from(array $input, array $filterable, array $sortable, int $defaultPerPage, int $maxPerPage): array
    {
        $filters = [];
        foreach ((array)($input['filter'] ?? []) as $k => $v) {
            if (in_array($k, $filterable, true)) {
                $filters[$k] = $v;
            }
        }

        $sort = [];
        if (!empty($input['sort'])) {
            $parts = array_filter(array_map('trim', explode(',', (string)$input['sort'])));
            foreach ($parts as $p) {
                $dir = str_starts_with($p, '-') ? 'desc' : 'asc';
                $field = ltrim($p, '-');
                if (in_array($field, $sortable, true)) {
                    $sort[] = [$field, $dir];
                }
            }
        }

        $page = max(1, (int)($input['page'] ?? 1));
        $perPage = max(1, min((int)($input['perPage'] ?? $defaultPerPage), $maxPerPage));

        return compact('filters', 'sort', 'page', 'perPage');
    }
}
