<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Http\Controllers;

use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestCategory;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenericControllerTest extends TestCase
{
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResources();

        $this->categoryId = TestCategory::create(['name' => 'Electronics'])->id;
    }

    // --- INDEX ---

    #[Test]
    public function index_returns_paginated_list(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 499, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'price', 'category_id']],
                'meta' => ['page', 'perPage', 'total', 'lastPage'],
            ]);

        $this->assertEquals(2, $response->json('meta.total'));
        $this->assertEquals(1, $response->json('meta.page'));
    }

    #[Test]
    public function index_respects_per_page_config(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            TestProduct::create(['name' => "Product {$i}", 'price' => $i * 100, 'category_id' => $this->categoryId]);
        }

        $response = $this->getJson('/api/v1/product?perPage=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
        $this->assertEquals(5, $response->json('meta.total'));
        $this->assertEquals(2, $response->json('meta.perPage'));
        $this->assertEquals(3, $response->json('meta.lastPage'));
    }

    #[Test]
    public function index_filterable_fields_work(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 499, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?filter[name]=Phone');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertEquals('Phone', $response->json('data.0.name'));
    }

    #[Test]
    public function index_unknown_filter_returns_422(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        // 'id' is not in the filterable list
        $response = $this->getJson('/api/v1/product?filter[id]=1');

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['filter']]);
    }

    #[Test]
    public function index_unknown_sort_returns_422(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?sort=nonexistent');

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['sort']]);
    }

    #[Test]
    public function index_valid_filters_still_work(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 499, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?filter[name]=Phone');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function index_sortable_fields_work(): void
    {
        TestProduct::create(['name' => 'Banana Phone', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Apple Phone', 'price' => 200, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?sort=name');

        $response->assertOk();
        $this->assertEquals('Apple Phone', $response->json('data.0.name'));
        $this->assertEquals('Banana Phone', $response->json('data.1.name'));
    }

    #[Test]
    public function index_sort_descending(): void
    {
        TestProduct::create(['name' => 'Banana Phone', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Apple Phone', 'price' => 200, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?sort=-name');

        $response->assertOk();
        $this->assertEquals('Banana Phone', $response->json('data.0.name'));
    }

    #[Test]
    public function index_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            TestProduct::create(['name' => "Product {$i}", 'price' => $i * 100, 'category_id' => $this->categoryId]);
        }

        $response = $this->getJson('/api/v1/product?perPage=2&page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
        $this->assertEquals(2, $response->json('meta.page'));
    }

    // --- SEARCH ---

    #[Test]
    public function index_search_across_multiple_fields(): void
    {
        TestProduct::create(['name' => 'Widget Pro', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Phone', 'price' => 200, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?search=Widget');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertEquals('Widget Pro', $response->json('data.0.name'));
    }

    #[Test]
    public function index_search_with_no_results(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?search=NonExistent');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function index_search_empty_term_returns_all(): void
    {
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Tablet', 'price' => 200, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?search=');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_search_combined_with_filters(): void
    {
        TestProduct::create(['name' => 'Widget Pro', 'price' => 100, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Widget Basic', 'price' => 50, 'category_id' => $this->categoryId]);
        TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $response = $this->getJson('/api/v1/product?search=Widget&filter[price]=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertEquals('Widget Pro', $response->json('data.0.name'));
    }

    // --- SHOW ---

    #[Test]
    public function show_returns_single_resource(): void
    {
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $this->categoryId]);

        $response = $this->getJson("/api/v1/product/{$product->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'price', 'category_id']]);
        $this->assertEquals('Phone', $response->json('data.name'));
    }

    #[Test]
    public function show_returns_404_for_missing_resource(): void
    {
        $response = $this->getJson('/api/v1/product/9999');

        $response->assertNotFound();
    }

    // --- STORE ---

    #[Test]
    public function store_creates_resource_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/product', [
            'name'        => 'New Product',
            'price'       => 299,
            'category_id' => $this->categoryId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Product');

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/product', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price']);
    }

    #[Test]
    public function store_validates_field_rules(): void
    {
        $response = $this->postJson('/api/v1/product', [
            'name'        => \str_repeat('x', 200), // max:120
            'price'       => -1, // min:0
            'category_id' => $this->categoryId,
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function store_ignores_extra_fields_not_in_registry(): void
    {
        $response = $this->postJson('/api/v1/product', [
            'name'        => 'Product',
            'price'       => 100,
            'category_id' => $this->categoryId,
            'evil_field'  => 'should not persist',
        ]);

        $response->assertCreated();
    }

    // --- UPDATE ---

    #[Test]
    public function update_modifies_resource(): void
    {
        $product = TestProduct::create(['name' => 'Old', 'price' => 100, 'category_id' => $this->categoryId]);

        $response = $this->patchJson("/api/v1/product/{$product->id}", [
            'name' => 'Updated',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated']);
    }

    #[Test]
    public function update_supports_partial_updates(): void
    {
        $product = TestProduct::create(['name' => 'Phone', 'price' => 100, 'category_id' => $this->categoryId]);

        $response = $this->patchJson("/api/v1/product/{$product->id}", [
            'price' => 200,
        ]);

        $response->assertOk();
        $this->assertEquals('Phone', $response->json('data.name'));
        $this->assertEquals(200, $response->json('data.price'));
    }

    // --- DESTROY ---

    #[Test]
    public function destroy_deletes_resource(): void
    {
        // Use category (no policy -> no delete denial)
        $category = TestCategory::create(['name' => 'To Delete']);

        $response = $this->deleteJson("/api/v1/category/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // --- 404 for unknown resource ---

    #[Test]
    public function unknown_resource_returns_404(): void
    {
        $response = $this->getJson('/api/v1/nonexistent');

        $response->assertNotFound();
    }

    // --- Empty list ---

    #[Test]
    public function index_returns_empty_list_when_no_data(): void
    {
        $response = $this->getJson('/api/v1/product');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
        $this->assertEquals(0, $response->json('meta.total'));
    }
}
