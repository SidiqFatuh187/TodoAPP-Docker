<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\TaskOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee('Notifications');
    }

    public function test_user_can_filter_notifications(): void
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
            'title'       => 'Tugas Uji Coba',
            'status'      => 'pending',
            'deadline'    => now()->subDay(),
        ]);

        $user->notify(new TaskOverdue($todo));

        $responseAll = $this->actingAs($user)->get('/notifications?filter=all');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Tugas Uji Coba');

        $responseOverdue = $this->actingAs($user)->get('/notifications?filter=overdue');
        $responseOverdue->assertStatus(200);
        $responseOverdue->assertSee('Tugas Uji Coba');

        $responseDigest = $this->actingAs($user)->get('/notifications?filter=digest');
        $responseDigest->assertStatus(200);
        $responseDigest->assertSee('No Notifications');
    }

    public function test_user_can_mark_all_notifications_as_read(): void
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
            'title'       => 'Task Baru',
            'status'      => 'pending',
            'deadline'    => now()->subDay(),
        ]);

        $user->notify(new TaskOverdue($todo));
        $this->assertEquals(1, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)->post('/notification/mark-all-read');
        $response->assertRedirect();

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_read_and_redirect_marks_as_read_and_redirects(): void
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
            'title'       => 'Task Redirect',
            'status'      => 'pending',
            'deadline'    => now()->subDay(),
        ]);

        $user->notify(new TaskOverdue($todo));
        $notification = $user->unreadNotifications()->first();

        $response = $this->actingAs($user)->get("/notification/{$notification->id}/read");
        $response->assertRedirect("/todo/{$todo->id}");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_delete_selected_notifications(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);

        $todoA = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Task A',
            'status'      => 'pending',
            'deadline'    => now()->subDay(),
        ]);

        $todoB = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Task B',
            'status'      => 'pending',
            'deadline'    => now()->subDay(),
        ]);

        $user->notify(new TaskOverdue($todoA));
        $user->notify(new TaskOverdue($todoB));
        $this->assertEquals(2, $user->notifications()->count());

        $notifA = $user->notifications()->first();

        // Delete only notifA
        $response = $this->actingAs($user)->delete('/notifications/destroy-selected', [
            'ids' => [$notifA->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $user->fresh()->notifications()->count());
    }
}
