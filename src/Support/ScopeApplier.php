<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Applies a configured scope to a query if present.
 */
final class ScopeApplier
{
    /**
     * @param  Builder<Model> $query
     * @return Builder<Model>
     */
    public static function apply(string $resource, Builder $query, ?Authenticatable $user): Builder
    {
        $scope = config("generic_api.scopes.{$resource}");
        if (!$scope) {
            return $query;
        }
        if (\is_string($scope) && \class_exists($scope)) {
            $scope = app($scope);
        }

        /** @var callable(Builder<Model>, ?Authenticatable): Builder<Model> $scope */
        return $scope($query, $user);
    }
}
