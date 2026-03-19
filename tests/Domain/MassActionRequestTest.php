<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Domain;

use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MassActionRequestTest extends TestCase
{
    #[Test]
    public function constructor_sets_all_properties(): void
    {
        $request = new MassActionRequest(
            resource: 'product',
            action: 'clone',
            ids: [1, 2, 3],
            payload: ['reason' => 'test'],
        );

        self::assertSame('product', $request->resource);
        self::assertSame('clone', $request->action);
        self::assertSame([1, 2, 3], $request->ids);
        self::assertSame(['reason' => 'test'], $request->payload);
    }

    #[Test]
    public function payload_defaults_to_empty_array(): void
    {
        $request = new MassActionRequest(
            resource: 'product',
            action: 'delete',
            ids: [5],
        );

        self::assertSame([], $request->payload);
    }

    #[Test]
    public function ids_can_be_string_or_int(): void
    {
        $request = new MassActionRequest(
            resource: 'product',
            action: 'activate',
            ids: ['abc', 42, 'def'],
        );

        self::assertSame(['abc', 42, 'def'], $request->ids);
    }
}
