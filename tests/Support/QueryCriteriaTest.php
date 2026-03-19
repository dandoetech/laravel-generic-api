<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Support;

use DanDoeTech\LaravelGenericApi\Exceptions\InvalidCriteriaException;
use DanDoeTech\LaravelGenericApi\Support\QueryCriteria;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QueryCriteriaTest extends TestCase
{
    private const FILTERABLE = ['name', 'price', 'category_id'];
    private const SORTABLE = ['name', 'price', 'created_at'];

    #[Test]
    public function empty_input_returns_defaults(): void
    {
        $result = QueryCriteria::from([], self::FILTERABLE, self::SORTABLE, 25, 200);

        self::assertSame([], $result['filters']);
        self::assertSame([], $result['sort']);
        self::assertNull($result['search']);
        self::assertSame(1, $result['page']);
        self::assertSame(25, $result['perPage']);
    }

    #[Test]
    public function parses_simple_filter_as_eq_for_non_string_field(): void
    {
        $result = QueryCriteria::from(
            ['filter' => ['price' => '100']],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(1, $result['filters']);
        self::assertSame('price', $result['filters'][0]['field']);
        self::assertSame('eq', $result['filters'][0]['operator']);
        self::assertSame('100', $result['filters'][0]['value']);
    }

    #[Test]
    public function parses_simple_filter_as_like_for_string_field(): void
    {
        $result = QueryCriteria::from(
            ['filter' => ['name' => 'Widget']],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
            stringFields: ['name'],
        );

        self::assertCount(1, $result['filters']);
        self::assertSame('name', $result['filters'][0]['field']);
        self::assertSame('like', $result['filters'][0]['operator']);
        self::assertSame('Widget', $result['filters'][0]['value']);
    }

    #[Test]
    public function parses_operator_mode_filter(): void
    {
        $result = QueryCriteria::from(
            ['filter' => ['price' => ['gte' => '10', 'lte' => '100']]],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(2, $result['filters']);
        self::assertSame('gte', $result['filters'][0]['operator']);
        self::assertSame('10', $result['filters'][0]['value']);
        self::assertSame('lte', $result['filters'][1]['operator']);
        self::assertSame('100', $result['filters'][1]['value']);
    }

    #[Test]
    public function throws_for_unknown_filter_field(): void
    {
        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Unknown filter fields: unknown');

        QueryCriteria::from(
            ['filter' => ['unknown' => 'value']],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );
    }

    #[Test]
    public function throws_for_unknown_operator(): void
    {
        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Unknown operators: badop');

        QueryCriteria::from(
            ['filter' => ['price' => ['badop' => '10']]],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );
    }

    #[Test]
    public function parses_sort_ascending(): void
    {
        $result = QueryCriteria::from(
            ['sort' => 'name'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(1, $result['sort']);
        self::assertSame('name', $result['sort'][0][0]);
        self::assertSame('asc', $result['sort'][0][1]);
    }

    #[Test]
    public function parses_sort_descending(): void
    {
        $result = QueryCriteria::from(
            ['sort' => '-price'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(1, $result['sort']);
        self::assertSame('price', $result['sort'][0][0]);
        self::assertSame('desc', $result['sort'][0][1]);
    }

    #[Test]
    public function parses_multiple_sort_fields(): void
    {
        $result = QueryCriteria::from(
            ['sort' => '-price,name'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(2, $result['sort']);
        self::assertSame('price', $result['sort'][0][0]);
        self::assertSame('desc', $result['sort'][0][1]);
        self::assertSame('name', $result['sort'][1][0]);
        self::assertSame('asc', $result['sort'][1][1]);
    }

    #[Test]
    public function throws_for_unknown_sort_field(): void
    {
        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Unknown sort fields: unknown');

        QueryCriteria::from(
            ['sort' => 'unknown'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );
    }

    #[Test]
    public function parses_search_term(): void
    {
        $result = QueryCriteria::from(
            ['search' => 'Widget'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame('Widget', $result['search']);
    }

    #[Test]
    public function empty_search_returns_null(): void
    {
        $result = QueryCriteria::from(
            ['search' => ''],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertNull($result['search']);
    }

    #[Test]
    public function non_string_search_returns_null(): void
    {
        $result = QueryCriteria::from(
            ['search' => 42],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertNull($result['search']);
    }

    #[Test]
    public function parses_page_and_per_page(): void
    {
        $result = QueryCriteria::from(
            ['page' => '3', 'perPage' => '50'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame(3, $result['page']);
        self::assertSame(50, $result['perPage']);
    }

    #[Test]
    public function page_minimum_is_one(): void
    {
        $result = QueryCriteria::from(
            ['page' => '-5'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame(1, $result['page']);
    }

    #[Test]
    public function per_page_capped_at_max(): void
    {
        $result = QueryCriteria::from(
            ['perPage' => '999'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame(200, $result['perPage']);
    }

    #[Test]
    public function non_numeric_page_defaults_to_one(): void
    {
        $result = QueryCriteria::from(
            ['page' => 'abc'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame(1, $result['page']);
    }

    #[Test]
    public function non_numeric_per_page_defaults_to_default(): void
    {
        $result = QueryCriteria::from(
            ['perPage' => 'abc'],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame(25, $result['perPage']);
    }

    #[Test]
    public function empty_sort_string_is_ignored(): void
    {
        $result = QueryCriteria::from(
            ['sort' => ''],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame([], $result['sort']);
    }

    #[Test]
    public function non_string_sort_is_ignored(): void
    {
        $result = QueryCriteria::from(
            ['sort' => 42],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertSame([], $result['sort']);
    }

    #[Test]
    public function between_filter_operator_accepted(): void
    {
        $result = QueryCriteria::from(
            ['filter' => ['price' => ['between' => '10,100']]],
            self::FILTERABLE,
            self::SORTABLE,
            25,
            200,
        );

        self::assertCount(1, $result['filters']);
        self::assertSame('between', $result['filters'][0]['operator']);
        self::assertSame('10,100', $result['filters'][0]['value']);
    }
}
