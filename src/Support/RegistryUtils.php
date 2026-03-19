<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Support;

use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Definition\FieldType;

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
        return \array_map(fn ($f) => $f->getName(), $res->getFields());
    }

    /**
     * Returns names of fields and computed fields that are string-like types.
     *
     * @return list<string>
     */
    public static function stringFieldNames(ResourceDefinitionInterface $res): array
    {
        $stringLike = [FieldType::String, FieldType::Text, FieldType::Email, FieldType::Url, FieldType::Enum];

        $names = [];
        foreach ($res->getFields() as $field) {
            if (\in_array($field->getType(), $stringLike, true)) {
                $names[] = $field->getName();
            }
        }
        foreach ($res->getComputedFields() as $computed) {
            if (\in_array($computed->getType(), $stringLike, true)) {
                $names[] = $computed->getName();
            }
        }

        return $names;
    }
}
