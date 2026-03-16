<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Repository;

use DanDoeTech\LaravelGenericApi\Domain\EloquentRepositoryAdapter;
use DanDoeTech\LaravelGenericApi\Domain\QueryApplier;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestCategory;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use PHPUnit\Framework\Attributes\Test;

final class EloquentRepositoryAdapterTest extends TestCase
{
    private EloquentRepositoryAdapter $repo;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResources();

        \assert($this->app !== null);
        $queryApplier = new QueryApplier(new ViaResolverFactory(), $this->app);
        $this->repo = new EloquentRepositoryAdapter(
            $this->app->make(Registry::class),
            $queryApplier,
            $this->app,
        );

        $this->categoryId = TestCategory::create(['name' => 'Electronics'])->id;
    }

    // --- paginate ---

    #[Test]
    public function paginate_returns_data_and_meta(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 499, 'category_id' => $this->categoryId]);

        $result = $this->repo->paginate('product', [
            'filters' => [],
            'sort'    => [],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['meta']['total']);
        $this->assertEquals(1, $result['meta']['page']);
    }

    #[Test]
    public function paginate_filters_regular_fields(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 499, 'category_id' => $this->categoryId]);

        $result = $this->repo->paginate('product', [
            'filters' => [['field' => 'name', 'operator' => 'like', 'value' => 'Phone']],
            'sort'    => [],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Phone', $result['data'][0]['name']);
    }

    #[Test]
    public function paginate_sorts_on_regular_fields(): void
    {
        TestProduct::create(['name' => 'Banana', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Apple', 'price' => 200, 'category_id' => $this->categoryId]);

        $result = $this->repo->paginate('product', [
            'filters' => [],
            'sort'    => [['name', 'asc']],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertEquals('Apple', $result['data'][0]['name']);
        $this->assertEquals('Banana', $result['data'][1]['name']);
    }

    #[Test]
    public function paginate_sorts_descending(): void
    {
        TestProduct::create(['name' => 'Apple', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Banana', 'price' => 200, 'category_id' => $this->categoryId]);

        $result = $this->repo->paginate('product', [
            'filters' => [],
            'sort'    => [['name', 'desc']],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertEquals('Banana', $result['data'][0]['name']);
    }

    #[Test]
    public function paginate_respects_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            TestProduct::create(['name' => "P{$i}", 'price' => $i * 100, 'category_id' => $this->categoryId]);
        }

        $result = $this->repo->paginate('product', [
            'filters' => [],
            'sort'    => [],
            'page'    => 2,
            'perPage' => 2,
        ]);

        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['meta']['page']);
        $this->assertEquals(5, $result['meta']['total']);
        $this->assertEquals(3, $result['meta']['lastPage']);
    }

    // --- find ---

    #[Test]
    public function find_returns_model_by_id(): void
    {
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        $result = $this->repo->find('product', (string) $product->id);

        $this->assertNotNull($result);
        $this->assertEquals('Phone', $result['name']);
    }

    #[Test]
    public function find_returns_null_for_missing_id(): void
    {
        $result = $this->repo->find('product', '9999');

        $this->assertNull($result);
    }

    #[Test]
    public function find_includes_computed_fields(): void
    {
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        $result = $this->repo->find('product', (string) $product->id);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('category_name', $result);
        $this->assertEquals('Electronics', $result['category_name']);
    }

    // --- create ---

    #[Test]
    public function create_persists_and_returns_model(): void
    {
        $result = $this->repo->create('product', [
            'name'        => 'New Phone',
            'price'       => 799,
            'category_id' => $this->categoryId,
        ]);

        $this->assertEquals('New Phone', $result['name']);
        $this->assertDatabaseHas('products', ['name' => 'New Phone']);
    }

    // --- update ---

    #[Test]
    public function update_modifies_and_returns_model(): void
    {
        $product = TestProduct::create(['name' => 'Old', 'price' => 100, 'category_id' => $this->categoryId]);

        $result = $this->repo->update('product', $product->id, ['name' => 'New']);

        $this->assertEquals('New', $result['name']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New']);
    }

    // --- delete ---

    #[Test]
    public function delete_removes_model(): void
    {
        $product = TestProduct::create(['name' => 'To Delete', 'price' => 100, 'category_id' => $this->categoryId]);

        $this->repo->delete('product', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    // --- error cases ---

    #[Test]
    public function paginate_unknown_resource_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repo->paginate('nonexistent', ['filters' => [], 'sort' => [], 'page' => 1, 'perPage' => 25]);
    }
}
