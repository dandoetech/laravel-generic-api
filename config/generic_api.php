<?php

declare(strict_types=1);

return [
    'prefix' => env('GENERIC_API_PREFIX', 'api/v1'),

    // Map resource key -> Eloquent model
    'resource_to_model' => [
        // 'product' => App\Models\Product::class,
    ],

    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 200,
    ],

    // Allowlist for filtering/sorting (fallbacks still exist in controller)
    'query' => [
        // 'product' => [
        //   'filterable' => ['name','price','category_id','created_at'],
        //   'sortable'   => ['id','name','price','created_at'],
        // ],
    ],

    // Named profiles you can pick via ?profile=admin (CriteriaProfile)
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
