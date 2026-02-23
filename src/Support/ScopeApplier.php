<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies a configured scope to a query if present.
 */
final class ScopeApplier
{
    /** @param Builder $query */
    public static function apply(string $resource, $query, ?Authenticatable $user): Builder
    {
        $scope = config("generic_api.scopes.{$resource}");
        if (!$scope) {
            return $query;
        }
        if (is_string($scope) && class_exists($scope)) {
            $scope = app($scope);
        }
        /** @var callable $scope */
        return $scope($query, $user);
    }
}
