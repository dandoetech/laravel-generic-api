<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Repository;

use DanDoeTech\LaravelGenericApi\Domain\EloquentRepositoryAdapter;
use DanDoeTech\LaravelGenericApi\Domain\QueryApplier;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestNote;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestUser;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use PHPUnit\Framework\Attributes\Test;

final class OwnerScopeTest extends TestCase
{
    private EloquentRepositoryAdapter $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResourcesWithOwnerScope();

        \assert($this->app !== null);
        $queryApplier = new QueryApplier(new ViaResolverFactory(), $this->app);
        $this->repo = new EloquentRepositoryAdapter(
            $this->app->make(Registry::class),
            $queryApplier,
            $this->app,
        );
    }

    #[Test]
    public function query_with_owner_scope_filters_by_user(): void
    {
        $user1 = TestUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $user2 = TestUser::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        TestNote::create(['title' => 'Alice Note', 'user_id' => $user1->id]);
        TestNote::create(['title' => 'Bob Note', 'user_id' => $user2->id]);

        $this->actingAs($user1);

        $result = $this->repo->paginate('note', [
            'filters' => [],
            'sort'    => [],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Alice Note', $result['data'][0]['title']);
    }

    #[Test]
    public function query_with_owner_scope_returns_empty_for_guest(): void
    {
        $user1 = TestUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        TestNote::create(['title' => 'Alice Note', 'user_id' => $user1->id]);

        // No actingAs — guest user
        $result = $this->repo->paginate('note', [
            'filters' => [],
            'sort'    => [],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertCount(0, $result['data']);
    }

    #[Test]
    public function query_without_scope_returns_all(): void
    {
        $user1 = TestUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $user2 = TestUser::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        // Products use ProductResource which has no HasOwnerScope
        $categoryId = \DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestCategory::create(['name' => 'Cat'])->id;
        \DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct::create(['name' => 'P1', 'price' => 10, 'category_id' => $categoryId]);
        \DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestProduct::create(['name' => 'P2', 'price' => 20, 'category_id' => $categoryId]);

        $this->actingAs($user1);

        $result = $this->repo->paginate('product', [
            'filters' => [],
            'sort'    => [],
            'page'    => 1,
            'perPage' => 25,
        ]);

        $this->assertCount(2, $result['data']);
    }
}
