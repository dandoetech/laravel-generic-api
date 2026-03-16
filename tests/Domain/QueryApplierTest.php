<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Domain;

use DanDoeTech\LaravelGenericApi\Domain\QueryApplier;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestCategory;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;

final class QueryApplierTest extends TestCase
{
    private QueryApplier $applier;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResources();

        \assert($this->app !== null);
        $this->applier = new QueryApplier(new ViaResolverFactory(), $this->app);

        $this->categoryId = TestCategory::create(['name' => 'Electronics'])->id;
    }

    // --- Computed Fields ---

    #[Test]
    public function apply_adds_computed_fields(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        $res = $this->resource();
        /** @var Builder<Model> $builder */
        $builder = TestProduct::query();
        $builder = $this->applier->applyComputedFieldsOnly($builder, $res);

        /** @var array<string, mixed> $result */
        $result = $builder->first()?->toArray();

        $this->assertNotNull($result);
        $this->assertArrayHasKey('category_name', $result);
        $this->assertEquals('Electronics', $result['category_name']);
    }

    // --- Filters ---

    #[Test]
    public function apply_filter_eq(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [['field' => 'price', 'operator' => 'eq', 'value' => 100]],
        ]);

        $this->assertCount(1, $builder->get());
        $this->assertEquals('Phone', $builder->first()?->toArray()['name']);
    }

    #[Test]
    public function apply_filter_like(): void
    {
        TestProduct::create(['name' => 'Widget Pro', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Phone', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [['field' => 'name', 'operator' => 'like', 'value' => 'Widget']],
        ]);

        $this->assertCount(1, $builder->get());
        $this->assertEquals('Widget Pro', $builder->first()?->toArray()['name']);
    }

    #[Test]
    public function apply_filter_between(): void
    {
        TestProduct::create(['name' => 'Cheap', 'price' => 10, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Mid', 'price' => 50, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Expensive', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [['field' => 'price', 'operator' => 'between', 'value' => '20,100']],
        ]);

        $this->assertCount(1, $builder->get());
        $this->assertEquals('Mid', $builder->first()?->toArray()['name']);
    }

    #[Test]
    public function apply_filter_gte_and_lte_combined(): void
    {
        TestProduct::create(['name' => 'Cheap', 'price' => 10, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Mid', 'price' => 50, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Expensive', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [
                ['field' => 'price', 'operator' => 'gte', 'value' => 20],
                ['field' => 'price', 'operator' => 'lte', 'value' => 100],
            ],
        ]);

        $this->assertCount(1, $builder->get());
        $this->assertEquals('Mid', $builder->first()?->toArray()['name']);
    }

    #[Test]
    public function apply_filter_computed_field_adds_having_clause(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [['field' => 'category_name', 'operator' => 'like', 'value' => 'Elec']],
        ]);

        // SQLite doesn't support HAVING on non-aggregate queries, so verify SQL instead
        $sql = $builder->toSql();
        $this->assertStringContainsString('having', \strtolower($sql));
        $this->assertStringContainsString('category_name', $sql);
    }

    // --- Search ---

    #[Test]
    public function apply_search_or_like_across_fields(): void
    {
        TestProduct::create(['name' => 'Widget Pro', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Phone', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith(['search' => 'Widget']);

        $this->assertCount(1, $builder->get());
        $this->assertEquals('Widget Pro', $builder->first()?->toArray()['name']);
    }

    #[Test]
    public function apply_search_empty_term_no_effect(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith(['search' => '']);

        $this->assertCount(2, $builder->get());
    }

    #[Test]
    public function apply_search_null_term_no_effect(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith(['search' => null]);

        $this->assertCount(1, $builder->get());
    }

    // --- Sort ---

    #[Test]
    public function apply_sort_ascending(): void
    {
        TestProduct::create(['name' => 'Banana', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Apple', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith(['sort' => [['name', 'asc']]]);

        /** @var list<array<string, mixed>> $results */
        $results = $builder->get()->map(fn (Model $m) => $m->toArray())->all();
        $this->assertEquals('Apple', $results[0]['name']);
        $this->assertEquals('Banana', $results[1]['name']);
    }

    #[Test]
    public function apply_sort_descending(): void
    {
        TestProduct::create(['name' => 'Apple', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Banana', 'price' => 200, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith(['sort' => [['name', 'desc']]]);

        $this->assertEquals('Banana', $builder->first()?->toArray()['name']);
    }

    // --- Combined ---

    #[Test]
    public function apply_combined_filter_search_sort(): void
    {
        TestProduct::create(['name' => 'Widget A', 'price' => 50, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Widget B', 'price' => 150, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $builder = $this->applyWith([
            'filters' => [['field' => 'price', 'operator' => 'gte', 'value' => 50]],
            'search'  => 'Widget',
            'sort'    => [['price', 'desc']],
        ]);

        /** @var list<array<string, mixed>> $results */
        $results = $builder->get()->map(fn (Model $m) => $m->toArray())->all();
        $this->assertCount(2, $results);
        $this->assertEquals('Widget B', $results[0]['name']);
        $this->assertEquals('Widget A', $results[1]['name']);
    }

    /**
     * @param  array<string, mixed> $criteria
     * @return Builder<Model>
     */
    private function applyWith(array $criteria): Builder
    {
        \assert($this->app !== null);
        $res = $this->app->make(Registry::class)->getResource('product');
        \assert($res instanceof ResourceDefinitionInterface);

        /** @var Builder<Model> $builder */
        $builder = TestProduct::query();

        return $this->applier->apply($builder, $res, $criteria);
    }

    private function resource(): ResourceDefinitionInterface
    {
        \assert($this->app !== null);
        $res = $this->app->make(Registry::class)->getResource('product');
        \assert($res instanceof ResourceDefinitionInterface);

        return $res;
    }
}
