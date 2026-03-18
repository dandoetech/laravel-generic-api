<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasOwnerScope;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasPolicy;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

final class OwnerScopedWithPolicyResource extends Resource implements HasEloquentModel, HasOwnerScope, HasPolicy
{
    public function model(): string
    {
        return TestNote::class;
    }

    public function ownerKey(): string
    {
        return 'user_id';
    }

    public function policy(): string
    {
        return NotePolicy::class;
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('secure_note')
            ->version(1)
            ->label('Secure Note')
            ->field('title', FieldType::String, nullable: false, rules: ['required'])
            ->field('user_id', FieldType::Integer, nullable: false)
            ->filterable(['title'])
            ->sortable(['title'])
            ->action('create')
            ->action('update')
            ->action('delete');
    }
}
