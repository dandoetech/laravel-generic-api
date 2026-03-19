<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

/**
 * A resource with custom middleware defined via meta — used to test
 * per-resource middleware configuration.
 */
final class AdminOnlyResource extends Resource implements HasEloquentModel
{
    public function model(): string
    {
        return TestCategory::class;
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('admin-category')
            ->version(1)
            ->label('Admin Category')
            ->field('name', FieldType::String, nullable: false, rules: ['required'])
            ->filterable(['name'])
            ->sortable(['name'])
            ->action('create')
            ->action('update')
            ->meta(['middleware' => ['auth:sanctum', 'can:admin']]);
    }
}
