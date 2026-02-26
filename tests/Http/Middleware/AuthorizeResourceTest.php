<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Http\Middleware;

use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestCategory;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AuthorizeResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResources();
    }

    #[Test]
    public function public_resource_without_policy_passes_through(): void
    {
        TestCategory::create(['name' => 'Electronics']);

        $response = $this->getJson('/api/v1/category');

        $response->assertOk();
    }

    #[Test]
    public function resource_with_policy_viewAny_authorized_returns_200(): void
    {
        $category = TestCategory::create(['name' => 'Electronics']);
        TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $category->id]);

        // TestProductPolicy::viewAny returns true
        $response = $this->getJson('/api/v1/product');

        $response->assertOk();
    }

    #[Test]
    public function resource_with_policy_delete_denied_returns_403(): void
    {
        $category = TestCategory::create(['name' => 'Electronics']);
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $category->id]);

        // TestProductPolicy::delete returns false
        $response = $this->deleteJson("/api/v1/product/{$product->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function resource_with_policy_view_single_authorized(): void
    {
        $category = TestCategory::create(['name' => 'Electronics']);
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $category->id]);

        // TestProductPolicy::view returns true
        $response = $this->getJson("/api/v1/product/{$product->id}");

        $response->assertOk();
    }

    #[Test]
    public function resource_with_policy_create_authorized(): void
    {
        $category = TestCategory::create(['name' => 'Electronics']);

        // TestProductPolicy::create returns true
        $response = $this->postJson('/api/v1/product', [
            'name'        => 'Tablet',
            'price'       => 499,
            'category_id' => $category->id,
        ]);

        $response->assertCreated();
    }

    #[Test]
    public function resource_with_policy_update_authorized(): void
    {
        $category = TestCategory::create(['name' => 'Electronics']);
        $product = TestProduct::create(['name' => 'Phone', 'price' => 999, 'category_id' => $category->id]);

        // TestProductPolicy::update returns true
        $response = $this->patchJson("/api/v1/product/{$product->id}", [
            'name' => 'Updated Phone',
        ]);

        $response->assertOk();
    }

    #[Test]
    public function resource_with_policy_but_no_model_throws_runtime_exception(): void
    {
        $this->registerTestResourcesWithBroken();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has a policy but no model binding');

        $this->withoutExceptionHandling();
        $this->getJson('/api/v1/broken');
    }

    #[Test]
    public function unknown_resource_passes_through_middleware_and_404s_in_controller(): void
    {
        $response = $this->getJson('/api/v1/nonexistent');

        $response->assertNotFound();
    }
}
