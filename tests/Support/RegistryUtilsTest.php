<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Support;

use DanDoeTech\LaravelGenericApi\Support\RegistryUtils;
use DanDoeTech\ResourceRegistry\Definition\ComputedFieldDefinition;
use DanDoeTech\ResourceRegistry\Definition\FieldDefinition;
use DanDoeTech\ResourceRegistry\Definition\FieldType;
use DanDoeTech\ResourceRegistry\Definition\ResourceDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegistryUtilsTest extends TestCase
{
    #[Test]
    public function field_names_returns_all_field_names(): void
    {
        $res = new ResourceDefinition(
            key: 'product',
            label: 'Product',
            fields: [
                new FieldDefinition('name', FieldType::String),
                new FieldDefinition('price', FieldType::Float),
                new FieldDefinition('active', FieldType::Boolean),
            ],
        );

        self::assertSame(['name', 'price', 'active'], RegistryUtils::fieldNames($res));
    }

    #[Test]
    public function field_names_empty_when_no_fields(): void
    {
        $res = new ResourceDefinition(key: 'empty', label: 'Empty');

        self::assertSame([], RegistryUtils::fieldNames($res));
    }

    #[Test]
    public function required_field_names_returns_non_nullable_fields(): void
    {
        $res = new ResourceDefinition(
            key: 'product',
            label: 'Product',
            fields: [
                new FieldDefinition('name', FieldType::String, nullable: false),
                new FieldDefinition('description', FieldType::String, nullable: true),
                new FieldDefinition('price', FieldType::Float, nullable: false),
            ],
        );

        $required = RegistryUtils::requiredFieldNames($res);

        self::assertContains('name', $required);
        self::assertContains('price', $required);
        self::assertNotContains('description', $required);
    }

    #[Test]
    public function string_field_names_returns_string_like_types(): void
    {
        $res = new ResourceDefinition(
            key: 'product',
            label: 'Product',
            fields: [
                new FieldDefinition('name', FieldType::String),
                new FieldDefinition('description', FieldType::Text),
                new FieldDefinition('email', FieldType::Email),
                new FieldDefinition('website', FieldType::Url),
                new FieldDefinition('status', FieldType::Enum),
                new FieldDefinition('price', FieldType::Float),
                new FieldDefinition('count', FieldType::Integer),
                new FieldDefinition('active', FieldType::Boolean),
            ],
        );

        $stringFields = RegistryUtils::stringFieldNames($res);

        self::assertContains('name', $stringFields);
        self::assertContains('description', $stringFields);
        self::assertContains('email', $stringFields);
        self::assertContains('website', $stringFields);
        self::assertContains('status', $stringFields);
        self::assertNotContains('price', $stringFields);
        self::assertNotContains('count', $stringFields);
        self::assertNotContains('active', $stringFields);
    }

    #[Test]
    public function string_field_names_includes_computed_string_fields(): void
    {
        $res = new ResourceDefinition(
            key: 'product',
            label: 'Product',
            fields: [
                new FieldDefinition('name', FieldType::String),
            ],
            computedFields: [
                new ComputedFieldDefinition('category_name', FieldType::String, via: 'category.name'),
                new ComputedFieldDefinition('orders_count', FieldType::Integer, via: 'count:orders'),
            ],
        );

        $stringFields = RegistryUtils::stringFieldNames($res);

        self::assertContains('name', $stringFields);
        self::assertContains('category_name', $stringFields);
        self::assertNotContains('orders_count', $stringFields);
    }
}
