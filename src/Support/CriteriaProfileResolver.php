<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

/**
 * Resolves filterable/sortable config for a resource given a profile name.
 *
 * Returns null when no matching profile is found.
 *
 * @deprecated Define query profiles on the Resource class via queryProfile() instead.
 *             This config-based resolver is kept for backwards compatibility only.
 */
final class CriteriaProfileResolver
{
    /**
     * @return array{filterable: list<string>, sortable: list<string>}|null
     */
    public static function resolve(string $resource, string $profile): ?array
    {
        /** @var array<string, array{filterable?: list<string>, sortable?: list<string>}> $profiles */
        $profiles = (array) config("ddt_api.query_profiles.{$resource}", []);

        if (!isset($profiles[$profile])) {
            return null;
        }

        $cfg = $profiles[$profile];

        return [
            'filterable' => $cfg['filterable'] ?? [],
            'sortable'   => $cfg['sortable'] ?? [],
        ];
    }
}
