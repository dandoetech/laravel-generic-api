<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Http\Controllers;

use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestNote;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\TestUser;
use DanDoeTech\LaravelGenericApi\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for security: HasOwnerScope query scoping + Policy enforcement
 * via HTTP requests through the full middleware + controller stack.
 */
final class SecurityTest extends TestCase
{
    private TestUser $user1;

    private TestUser $user2;

    private TestUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestResourcesWithOwnerScopeAndPolicy();

        $this->user1 = TestUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $this->user2 = TestUser::create(['name' => 'Bob', 'email' => 'bob@test.com']);
        $this->admin = TestUser::create(['name' => 'Admin', 'email' => 'admin@test.com']);
    }

    // --- Owner Scope: List filtering ---

    #[Test]
    public function owner_scope_list_returns_only_own_records(): void
    {
        TestNote::create(['title' => 'Alice Note 1', 'user_id' => $this->user1->id]);
        TestNote::create(['title' => 'Alice Note 2', 'user_id' => $this->user1->id]);
        TestNote::create(['title' => 'Alice Note 3', 'user_id' => $this->user1->id]);
        TestNote::create(['title' => 'Bob Note 1', 'user_id' => $this->user2->id]);
        TestNote::create(['title' => 'Bob Note 2', 'user_id' => $this->user2->id]);

        $response1 = $this->actingAs($this->user1)->getJson('/api/v1/secure_note');
        $response1->assertOk()
            ->assertJsonCount(3, 'data');
        $this->assertEquals(3, $response1->json('meta.total'));

        $response2 = $this->actingAs($this->user2)->getJson('/api/v1/secure_note');
        $response2->assertOk()
            ->assertJsonCount(2, 'data');
        $this->assertEquals(2, $response2->json('meta.total'));
    }

    #[Test]
    public function owner_scope_show_denies_other_users_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Private', 'user_id' => $this->user1->id]);

        // Bob tries to view Alice's note — the AuthorizeResource middleware
        // fetches the model globally and the policy's view() checks ownership,
        // returning 403 before the owner scope in the repository is reached
        $response = $this->actingAs($this->user2)->getJson("/api/v1/secure_note/{$note->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function unauthenticated_request_on_owner_scoped_returns_empty(): void
    {
        TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);
        TestNote::create(['title' => 'Bob Note', 'user_id' => $this->user2->id]);

        // No actingAs — guest user; owner scope applies WHERE 1=0
        $response = $this->getJson('/api/v1/secure_note');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
        $this->assertEquals(0, $response->json('meta.total'));
    }

    #[Test]
    public function owner_scope_applies_to_all_users_including_admin(): void
    {
        TestNote::create(['title' => 'Admin Note', 'user_id' => $this->admin->id]);
        TestNote::create(['title' => 'Alice Note 1', 'user_id' => $this->user1->id]);
        TestNote::create(['title' => 'Alice Note 2', 'user_id' => $this->user1->id]);

        // HasOwnerScope doesn't check isAdmin — admin sees only their own notes
        $response = $this->actingAs($this->admin)->getJson('/api/v1/secure_note');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertEquals('Admin Note', $response->json('data.0.title'));
    }

    // --- Policy enforcement ---

    #[Test]
    public function policy_blocks_update_on_other_users_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        // Bob tries to update Alice's note — NotePolicy::update checks ownership
        $response = $this->actingAs($this->user2)->patchJson("/api/v1/secure_note/{$note->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'title' => 'Alice Note']);
    }

    #[Test]
    public function policy_blocks_delete_on_other_users_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        // Bob tries to delete Alice's note — NotePolicy::delete checks ownership
        $response = $this->actingAs($this->user2)->deleteJson("/api/v1/secure_note/{$note->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    #[Test]
    public function policy_allows_owner_to_update_own_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        $response = $this->actingAs($this->user1)->patchJson("/api/v1/secure_note/{$note->id}", [
            'title' => 'Updated by Alice',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated by Alice');
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'title' => 'Updated by Alice']);
    }

    #[Test]
    public function policy_allows_owner_to_delete_own_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        $response = $this->actingAs($this->user1)->deleteJson("/api/v1/secure_note/{$note->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    // --- Combined scope + policy ---

    #[Test]
    public function create_works_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user1)->postJson('/api/v1/secure_note', [
            'title'   => 'New Note',
            'user_id' => $this->user1->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'New Note')
            ->assertJsonPath('data.user_id', $this->user1->id);
        $this->assertDatabaseHas('notes', ['title' => 'New Note', 'user_id' => $this->user1->id]);
    }

    #[Test]
    public function owner_can_view_own_record(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        $response = $this->actingAs($this->user1)->getJson("/api/v1/secure_note/{$note->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'Alice Note');
    }

    #[Test]
    public function policy_blocks_view_for_non_owner(): void
    {
        $note = TestNote::create(['title' => 'Alice Note', 'user_id' => $this->user1->id]);

        // The middleware fetches the model globally for policy check,
        // so the policy's view method is called and denies access with 403
        $response = $this->actingAs($this->user2)->getJson("/api/v1/secure_note/{$note->id}");

        $response->assertForbidden();
    }
}
