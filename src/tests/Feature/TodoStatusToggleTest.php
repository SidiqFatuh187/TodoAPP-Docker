<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoStatusToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_todo_status_without_payload(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);

        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Test Toggle Status',
            'status'      => 'pending',
        ]);

        // Toggle 1: pending -> completed
        $response1 = $this->actingAs($user)->patchJson("/todo/status/{$todo->id}");
        $response1->assertStatus(200);
        $response1->assertJson(['success' => true, 'status' => 'completed']);
        $this->assertEquals('completed', $todo->fresh()->status);
        $this->assertNotNull($todo->fresh()->completed_at);

        // Toggle 2: completed -> pending
        $response2 = $this->actingAs($user)->patchJson("/todo/status/{$todo->id}");
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true, 'status' => 'pending']);
        $this->assertEquals('pending', $todo->fresh()->status);
        $this->assertNull($todo->fresh()->completed_at);
    }

    public function test_user_can_update_todo_status_with_explicit_status(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);

        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Test Explicit Status',
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($user)->patchJson("/todo/status/{$todo->id}", [
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'active']);
        $this->assertEquals('active', $todo->fresh()->status);
    }

    public function test_invalid_status_does_not_change_task(): void
    {
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Keep pending', 'status' => 'pending']);

        $this->actingAs($user)->patchJson("/todo/status/{$todo->id}", [
            'status' => 'not-a-status',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertSame('pending', $todo->fresh()->status);
        $this->assertNull($todo->fresh()->completed_at);
    }
}
