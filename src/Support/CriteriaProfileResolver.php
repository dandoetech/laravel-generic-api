<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

/**
 * Resolves filterable/sortable config for a resource given a profile name.
 *
 * Returns null when no matching profile is found.
 */
final class CriteriaProfileResolver
{
    /**
     * @return array{filterable: list<string>, sortable: list<string>}|null
     */
    public static function resolve(string $resource, string $profile): ?array
    {
        /** @var array<string, array{filterable?: list<string>, sortable?: list<string>}> $profiles */
        $profiles = (array) config("generic_api.query_profiles.{$resource}", []);

        if (!isset($profiles[$profile])) {
            return null;
        }

        $cfg = $profiles[$profile];

        return [
            'filterable' => \array_values($cfg['filterable'] ?? []),
            'sortable'   => \array_values($cfg['sortable'] ?? []),
        ];
    }
}
