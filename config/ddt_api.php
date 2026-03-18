<?php

declare(strict_types=1);

return [
    'prefix' => env('GENERIC_API_PREFIX', 'api/v1'),

    // Middleware applied to all generic API routes.
    // The AuthorizeResource middleware is always appended automatically.
    'middleware' => ['api'],

    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 200,
    ],

    // @deprecated Define query profiles on the Resource class via queryProfile() instead.
    // Config-based profiles are kept for backwards compatibility only.
    'query_profiles' => [
        // 'product' => [
        //   'default' => [ 'filterable' => ['name'], 'sortable' => ['id'] ],
        //   'admin'   => [ 'filterable' => ['name','price','category_id'], 'sortable' => ['-created_at','name'] ],
        // ],
    ],

    // Optional per-resource scope (closure or invokable) applied to list/show queries
    // Signature: fn(Builder $q, ?\Illuminate\Contracts\Auth\Authenticatable $user): Builder
    'scopes' => [
        // 'product' => App\Queries\ProductScope::class,
    ],

    // Mass action registry: action name => handler FQCN
    // Handler signature: handle(string $resource, array $ids, array $payload, \Illuminate\Contracts\Auth\Authenticatable|null $user): array
    'actions' => [
        // 'product' => [
        //    'bulk-delete' => App\Actions\ProductBulkDelete::class,
        //    'reprice'     => App\Actions\ProductReprice::class,
        // ],
    ],
];
