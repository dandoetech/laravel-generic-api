<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use DanDoeTech\ResourceRegistry\Definition\ResourceDefinition;

final class RegistryUtils
{
    /** @return list<string> */
    public static function requiredFieldNames(ResourceDefinition $res): array
    {
        return array_values(array_map(
            fn ($f) => $f->name,
            array_filter($res->fields, fn ($f) => $f->nullable === false)
        ));
    }

    /** @return list<string> */
    public static function fieldNames(ResourceDefinition $res): array
    {
        return array_values(array_map(fn ($f) => $f->name, $res->fields));
    }
}
