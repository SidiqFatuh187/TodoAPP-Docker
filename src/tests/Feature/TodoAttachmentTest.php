<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TodoAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_must_belong_to_the_task_owner_on_create_and_update(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::create(['user_id' => $other->id, 'name' => 'Private', 'color' => '#123456', 'icon' => 'tasks']);
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Original']);
        $payload = ['title' => 'Changed', 'priority' => 'medium', 'status' => 'pending', 'category_id' => $category->id];

        $this->actingAs($user)->post(route('todo.store'), $payload)->assertSessionHasErrors('category_id');
        $this->actingAs($user)->put(route('todo.update', $todo->id), $payload)->assertSessionHasErrors('category_id');

        $this->assertSame(1, Todo::count());
        $this->assertSame('Original', $todo->fresh()->title);
        $this->assertNull($todo->fresh()->category_id);
    }

    public function test_attachments_are_stored_privately_and_only_the_owner_can_download(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('todo.store'), [
            'title' => 'With attachment',
            'priority' => 'medium',
            'file' => UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('todo.index'));

        $todo = Todo::firstOrFail();
        Storage::disk('local')->assertExists($todo->file_path);
        Storage::disk('public')->assertMissing($todo->file_path);
        $this->get(route('todo.download', $todo->id))->assertOk()->assertDownload('brief.pdf');

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('todo.download', $todo->id))->assertNotFound();
        $this->actingAs($other)->get(route('todo.show', $todo->id))->assertNotFound();
        $this->actingAs($other)->put(route('todo.update', $todo->id), [
            'title' => 'Stolen', 'priority' => 'medium', 'status' => 'pending',
        ])->assertNotFound();
        $this->actingAs($other)->patchJson(route('todo.status', $todo->id))->assertNotFound();
        $this->actingAs($other)->delete(route('todo.delete', $todo->id))->assertNotFound();
        Storage::disk('local')->assertExists($todo->file_path);
    }

    public function test_download_requires_authentication_and_does_not_fall_back_to_public_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Legacy', 'file_path' => 'todo-files/legacy.pdf', 'file_name' => 'legacy.pdf']);
        Storage::disk('public')->put($todo->file_path, 'legacy bytes');

        $this->get(route('todo.download', $todo->id))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('todo.download', $todo->id))->assertNotFound();
    }

    public function test_replacing_attachment_removes_old_file_only_after_successful_save(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Original', 'file_path' => 'todo-files/old.pdf', 'file_name' => 'old.pdf']);
        Storage::disk('local')->put($todo->file_path, 'old bytes');

        $this->actingAs($user)->put(route('todo.update', $todo->id), [
            'title' => 'Changed', 'priority' => 'medium', 'status' => 'pending',
            'file' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('todo.index'));

        $todo->refresh();
        $this->assertSame('new.pdf', $todo->file_name);
        $this->assertNotSame('todo-files/old.pdf', $todo->file_path);
        Storage::disk('local')->assertMissing('todo-files/old.pdf');
        Storage::disk('local')->assertExists($todo->file_path);
        Storage::disk('public')->assertDirectoryEmpty('todo-files');
    }

    public function test_failed_replacement_keeps_old_file_and_removes_new_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Original', 'file_path' => 'todo-files/old.pdf', 'file_name' => 'old.pdf']);
        Storage::disk('local')->put($todo->file_path, 'old bytes');
        Todo::updating(function () {
            throw new \RuntimeException('Simulated database failure');
        });

        $this->actingAs($user)->put(route('todo.update', $todo->id), [
            'title' => 'Changed', 'priority' => 'medium', 'status' => 'pending',
            'file' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertSame('todo-files/old.pdf', $todo->fresh()->file_path);
        $this->assertSame('old bytes', Storage::disk('local')->get('todo-files/old.pdf'));
        $this->assertSame(['todo-files/old.pdf'], Storage::disk('local')->allFiles('todo-files'));
    }

    public function test_failed_create_cleans_up_new_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Todo::creating(function () {
            throw new \RuntimeException('Simulated database failure');
        });

        $this->actingAs($user)->post(route('todo.store'), [
            'title' => 'New', 'priority' => 'medium',
            'file' => UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertSame(0, Todo::count());
        Storage::disk('local')->assertDirectoryEmpty('todo-files');
    }

    public function test_owner_can_delete_task_and_private_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Remove', 'file_path' => 'todo-files/remove.pdf']);
        Storage::disk('local')->put($todo->file_path, 'remove bytes');

        $this->actingAs($user)->delete(route('todo.delete', $todo->id))->assertRedirect(route('todo.index'));

        $this->assertModelMissing($todo);
        Storage::disk('local')->assertMissing('todo-files/remove.pdf');
    }
}
