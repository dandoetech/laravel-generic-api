<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

/**
 * A public resource without a policy — used to test that authorization is skipped.
 */
final class CategoryResource extends Resource implements HasEloquentModel
{
    public function model(): string
    {
        return TestCategory::class;
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('category')
            ->version(1)
            ->label('Category')
            ->field('name', FieldType::String, nullable: false, rules: ['required'])
            ->filterable(['name'])
            ->sortable(['name'])
            ->action('create')
            ->action('update');
    }
}
