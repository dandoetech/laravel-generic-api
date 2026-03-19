<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Support;

use DanDoeTech\LaravelGenericApi\Http\Resources\ResourceJson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResourceJsonTest extends TestCase
{
    #[Test]
    public function item_wraps_data_in_data_key(): void
    {
        $data = ['id' => 1, 'name' => 'Widget'];

        $result = ResourceJson::item($data);

        self::assertSame(['data' => $data], $result);
    }

    #[Test]
    public function item_with_empty_data(): void
    {
        $result = ResourceJson::item([]);

        self::assertSame(['data' => []], $result);
    }

    #[Test]
    public function collection_wraps_items_and_meta(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Widget A'],
            ['id' => 2, 'name' => 'Widget B'],
        ];
        $meta = ['page' => 1, 'perPage' => 25, 'total' => 2, 'lastPage' => 1];

        $result = ResourceJson::collection($items, $meta);

        self::assertSame($items, $result['data']);
        self::assertSame($meta, $result['meta']);
    }

    #[Test]
    public function collection_with_empty_items(): void
    {
        $meta = ['page' => 1, 'perPage' => 25, 'total' => 0, 'lastPage' => 1];

        $result = ResourceJson::collection([], $meta);

        self::assertSame([], $result['data']);
        self::assertSame($meta, $result['meta']);
    }
}
