<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Exceptions;

use DanDoeTech\LaravelGenericApi\Exceptions\InvalidCriteriaException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvalidCriteriaExceptionTest extends TestCase
{
    #[Test]
    public function message_includes_unknown_filters(): void
    {
        $e = new InvalidCriteriaException(
            unknownFilters: ['foo', 'bar'],
            allowedFilters: ['name', 'price'],
        );

        self::assertStringContainsString('Unknown filter fields: foo, bar', $e->getMessage());
    }

    #[Test]
    public function message_includes_unknown_sorts(): void
    {
        $e = new InvalidCriteriaException(
            unknownSorts: ['baz'],
            allowedSorts: ['name'],
        );

        self::assertStringContainsString('Unknown sort fields: baz', $e->getMessage());
    }

    #[Test]
    public function message_includes_unknown_operators(): void
    {
        $e = new InvalidCriteriaException(unknownOperators: ['badop']);

        self::assertStringContainsString('Unknown operators: badop', $e->getMessage());
    }

    #[Test]
    public function message_combines_multiple_issues(): void
    {
        $e = new InvalidCriteriaException(
            unknownFilters: ['x'],
            unknownSorts: ['y'],
        );

        self::assertStringContainsString('Unknown filter fields: x', $e->getMessage());
        self::assertStringContainsString('Unknown sort fields: y', $e->getMessage());
    }

    #[Test]
    public function to_errors_returns_filter_errors(): void
    {
        $e = new InvalidCriteriaException(
            unknownFilters: ['foo'],
            allowedFilters: ['name', 'price'],
        );

        $errors = $e->toErrors();

        self::assertArrayHasKey('filter', $errors);
        self::assertStringContainsString('foo', $errors['filter'][0]);
        self::assertStringContainsString('name, price', $errors['filter'][0]);
    }

    #[Test]
    public function to_errors_returns_sort_errors(): void
    {
        $e = new InvalidCriteriaException(
            unknownSorts: ['baz'],
            allowedSorts: ['name'],
        );

        $errors = $e->toErrors();

        self::assertArrayHasKey('sort', $errors);
        self::assertStringContainsString('baz', $errors['sort'][0]);
        self::assertStringContainsString('name', $errors['sort'][0]);
    }

    #[Test]
    public function to_errors_returns_operator_errors(): void
    {
        $e = new InvalidCriteriaException(unknownOperators: ['badop']);

        $errors = $e->toErrors();

        self::assertArrayHasKey('operator', $errors);
        self::assertStringContainsString('badop', $errors['operator'][0]);
        self::assertStringContainsString('Allowed:', $errors['operator'][0]);
    }

    #[Test]
    public function to_errors_empty_when_no_issues(): void
    {
        $e = new InvalidCriteriaException();

        self::assertSame([], $e->toErrors());
    }
}
