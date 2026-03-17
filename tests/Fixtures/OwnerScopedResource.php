<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasOwnerScope;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

final class OwnerScopedResource extends Resource implements HasEloquentModel, HasOwnerScope
{
    public function model(): string
    {
        return TestNote::class;
    }

    public function ownerKey(): string
    {
        return 'user_id';
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('note')
            ->version(1)
            ->label('Note')
            ->field('title', FieldType::String, nullable: false, rules: ['required'])
            ->field('user_id', FieldType::Integer, nullable: false)
            ->filterable(['title'])
            ->sortable(['title'])
            ->action('create');
    }
}
