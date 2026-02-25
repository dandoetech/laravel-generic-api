<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;

final class RegistryUtils
{
    /** @return list<string> */
    public static function requiredFieldNames(ResourceDefinitionInterface $res): array
    {
        return \array_values(\array_map(
            fn ($f) => $f->getName(),
            \array_filter($res->getFields(), fn ($f) => $f->isNullable() === false),
        ));
    }

    /** @return list<string> */
    public static function fieldNames(ResourceDefinitionInterface $res): array
    {
        return \array_values(\array_map(fn ($f) => $f->getName(), $res->getFields()));
    }
}
