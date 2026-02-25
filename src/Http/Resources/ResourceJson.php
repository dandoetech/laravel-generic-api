<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Resources;

final class ResourceJson
{
    /**
     * @param  array<string, mixed>              $data
     * @return array{data: array<string, mixed>}
     */
    public static function item(array $data): array
    {
        return ['data' => $data];
    }

    /**
     * @param  list<array<string, mixed>>                                          $items
     * @param  array<string, mixed>                                                $meta
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public static function collection(array $items, array $meta): array
    {
        return ['data' => $items, 'meta' => $meta];
    }
}
