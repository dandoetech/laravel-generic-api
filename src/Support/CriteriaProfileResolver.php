<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

/**
 * Resolves filterable/sortable config for a resource given a profile name.
 */
final class CriteriaProfileResolver
{
    /**
     * @return array{filterable:list<string>, sortable:list<string>}
     */
    public static function resolve(string $resource, ?string $profile): array
    {
        $profiles = (array) config("generic_api.query_profiles.{$resource}", []);
        if ($profile && isset($profiles[$profile])) {
            $cfg = (array) $profiles[$profile];
            return [
                'filterable' => array_values($cfg['filterable'] ?? []),
                'sortable'   => array_values($cfg['sortable']   ?? []),
            ];
        }

        // Fallback to base query allowlist
        $base = (array) config("generic_api.query.{$resource}", []);
        return [
            'filterable' => array_values($base['filterable'] ?? []),
            'sortable'   => array_values($base['sortable']   ?? []),
        ];
    }
}
