<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PrivatizeTaskAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_never_writes_or_deletes_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('todo-files/legacy.pdf', 'legacy bytes');

        $this->artisan('tasks:privatize-attachments', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('legacy bytes', Storage::disk('public')->get('todo-files/legacy.pdf'));
        Storage::disk('local')->assertMissing('todo-files/legacy.pdf');
    }

    public function test_migration_preserves_paths_and_bytes_and_is_idempotent(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create();
        $todo = Todo::create(['user_id' => $user->id, 'title' => 'Legacy', 'file_path' => 'todo-files/legacy.pdf']);
        Storage::disk('public')->put($todo->file_path, 'legacy bytes');
        Storage::disk('public')->put('todo-files/orphan.pdf', 'orphan bytes');

        $this->artisan('tasks:privatize-attachments')->assertSuccessful();
        $this->artisan('tasks:privatize-attachments')->assertSuccessful();

        $this->assertSame('todo-files/legacy.pdf', $todo->fresh()->file_path);
        $this->assertSame('legacy bytes', Storage::disk('local')->get($todo->file_path));
        $this->assertSame('orphan bytes', Storage::disk('local')->get('todo-files/orphan.pdf'));
        Storage::disk('public')->assertDirectoryEmpty('todo-files');
    }

    public function test_retry_after_verified_copy_removes_only_matching_public_copy(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('todo-files/legacy.pdf', 'same bytes');
        Storage::disk('local')->put('todo-files/legacy.pdf', 'same bytes');

        $this->artisan('tasks:privatize-attachments')->assertSuccessful();

        Storage::disk('public')->assertMissing('todo-files/legacy.pdf');
        $this->assertSame('same bytes', Storage::disk('local')->get('todo-files/legacy.pdf'));
    }

    public function test_conflicting_private_copy_preserves_both_and_signals_failure(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('todo-files/legacy.pdf', 'original bytes');
        Storage::disk('local')->put('todo-files/legacy.pdf', 'different bytes');

        $this->artisan('tasks:privatize-attachments')->assertFailed();

        $this->assertSame('original bytes', Storage::disk('public')->get('todo-files/legacy.pdf'));
        $this->assertSame('different bytes', Storage::disk('local')->get('todo-files/legacy.pdf'));
    }

    public function test_failed_copy_retains_public_original_and_signals_failure(): void
    {
        $public = Storage::fake('public');
        $public->put('todo-files/legacy.pdf', 'original bytes');
        $private = Mockery::mock(FilesystemAdapter::class);
        $private->shouldReceive('exists')->with('todo-files/legacy.pdf')->andReturnFalse();
        $private->shouldReceive('put')->once()->with('todo-files/legacy.pdf.migrating', Mockery::type('resource'))->andReturnFalse();
        Storage::shouldReceive('disk')->with('public')->andReturn($public);
        Storage::shouldReceive('disk')->with('local')->andReturn($private);

        $this->artisan('tasks:privatize-attachments')->assertFailed();

        $this->assertSame('original bytes', $public->get('todo-files/legacy.pdf'));
    }

    public function test_failed_checksum_retains_original_and_retry_replaces_partial_staging_file(): void
    {
        $public = Storage::fake('public');
        $local = Storage::fake('local');
        $manager = Storage::getFacadeRoot();
        $public->put('todo-files/legacy.pdf', 'original bytes');
        $private = Mockery::mock(FilesystemAdapter::class);
        $private->shouldReceive('exists')->with('todo-files/legacy.pdf')->andReturnFalse();
        $private->shouldReceive('put')->once()->andReturnUsing(function ($path) use ($local) {
            return $local->put($path, 'truncated bytes');
        });
        $private->shouldReceive('path')->with('todo-files/legacy.pdf.migrating')
            ->andReturn($local->path('todo-files/legacy.pdf.migrating'));
        Storage::shouldReceive('disk')->with('public')->andReturn($public);
        Storage::shouldReceive('disk')->with('local')->andReturn($private);

        $this->artisan('tasks:privatize-attachments')->assertFailed();

        $this->assertSame('original bytes', $public->get('todo-files/legacy.pdf'));
        $local->assertMissing('todo-files/legacy.pdf');
        Storage::swap($manager);

        $this->artisan('tasks:privatize-attachments')->assertSuccessful();

        $public->assertMissing('todo-files/legacy.pdf');
        $local->assertMissing('todo-files/legacy.pdf.migrating');
        $this->assertSame('original bytes', $local->get('todo-files/legacy.pdf'));
    }

    public function test_missing_referenced_attachment_signals_failure(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create();
        Todo::create(['user_id' => $user->id, 'title' => 'Missing', 'file_path' => 'todo-files/missing.pdf']);

        $this->artisan('tasks:privatize-attachments')->assertFailed();
    }
}
