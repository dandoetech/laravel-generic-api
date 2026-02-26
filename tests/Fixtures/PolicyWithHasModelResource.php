<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasPolicy;
use DanDoeTech\ResourceRegistry\Builder\ResourceBuilder;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Resource;

/**
 * A resource with HasPolicy but NOT HasEloquentModel — should trigger RuntimeException.
 */
final class PolicyWithHasModelResource extends Resource implements HasPolicy
{
    public function policy(): string
    {
        return TestProductPolicy::class;
    }

    protected function define(ResourceBuilder $builder): void
    {
        $builder->key('broken')
            ->version(1)
            ->label('Broken')
            ->field('name', FieldType::String, nullable: false)
            ->action('create');
    }
}
