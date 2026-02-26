<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasPolicy;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

final class ProductResource extends Resource implements HasEloquentModel, HasPolicy
{
    public function model(): string
    {
        return TestProduct::class;
    }

    public function policy(): string
    {
        return TestProductPolicy::class;
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('product')
            ->version(1)
            ->label('Product')
            ->timestamps()
            ->field('name', FieldType::String, nullable: false, rules: ['required', 'max:120'])
            ->field('price', FieldType::Float, nullable: false, rules: ['required', 'numeric', 'min:0'])
            ->field('category_id', FieldType::Integer, nullable: false)
            ->belongsTo('category', target: 'category', foreignKey: 'category_id')
            ->filterable(['name', 'price', 'category_id'])
            ->sortable(['name', 'price', 'created_at'])
            ->searchable(['name'])
            ->action('create')
            ->action('update')
            ->action('delete');
    }
}
