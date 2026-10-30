<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }
    public function test_get_method_is_supported_for_fonnte_verification(): void
    {
        $response = $this->getJson('/webhook/fonnte');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'active']);
    }

    public function test_empty_payload_is_ignored(): void
    {
        $response = $this->postJson('/webhook/fonnte', []);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ignored',
            'reason' => 'empty_payload',
        ]);
    }

    public function test_unregistered_phone_returns_registration_notice(): void
    {
        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '628999999999',
            'message' => 'DONE 1',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply']);
        $this->assertStringContainsString('belum terdaftar', $response->json('reply'));
    }

    public function test_banned_user_is_rejected(): void
    {
        User::factory()->create([
            'phone'     => '081234567890',
            'is_banned' => true,
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TUGAS',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('dinonaktifkan', $response->json('reply'));
    }

    public function test_done_command_completes_task_and_logs_activity(): void
    {
        $user = User::factory()->create([
            'name'  => 'Sidiq',
            'phone' => '081234567890',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Kuliah',
            'color'   => '#3b82f6',
            'icon'    => 'book',
        ]);

        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Laporan Basis Data',
            'status'      => 'pending',
            'priority'    => 'high',
            'deadline'    => now()->addDay(),
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => "DONE {$todo->id}",
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('berhasil ditandai SELESAI', $response->json('reply'));
        $this->assertStringContainsString('Laporan Basis Data', $response->json('reply'));

        $todo->refresh();
        $this->assertEquals('completed', $todo->status);
        $this->assertNotNull($todo->completed_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'type'    => 'complete',
        ]);
    }

    public function test_done_command_rejects_task_belonging_to_another_user(): void
    {
        $userA = User::factory()->create(['phone' => '081234567801']);
        $userB = User::factory()->create(['phone' => '081234567802']);

        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name'    => 'Privat',
            'color'   => '#ef4444',
            'icon'    => 'lock',
        ]);

        $todoB = Todo::create([
            'user_id'     => $userB->id,
            'category_id' => $categoryB->id,
            'title'       => 'Tugas Rahasia User B',
            'status'      => 'pending',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567801',
            'message' => "DONE {$todoB->id}",
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('tidak ditemukan atau bukan milik kamu', $response->json('reply'));

        $todoB->refresh();
        $this->assertEquals('pending', $todoB->status);
    }

    public function test_snooze_command_extends_deadline(): void
    {
        $user = User::factory()->create([
            'phone'    => '081234567890',
            'timezone' => 'Asia/Jakarta',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Kerja',
            'color'   => '#10b981',
            'icon'    => 'briefcase',
        ]);

        $originalDeadline = Carbon::now('Asia/Jakarta')->addHours(3);
        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Desain UI Web',
            'status'      => 'pending',
            'deadline'    => $originalDeadline,
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => "TUNDA {$todo->id} 2h",
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('diundur 2 jam', $response->json('reply'));

        $todo->refresh();
        $this->assertTrue(Carbon::parse($todo->deadline)->gt($originalDeadline));
    }

    public function test_quick_create_command_creates_todo(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TODO Beli tiket seminar',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Tugas baru berhasil dicatat', $response->json('reply'));
        $this->assertStringContainsString('Beli tiket seminar', $response->json('reply'));

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title'   => 'Beli tiket seminar',
            'status'  => 'pending',
        ]);
    }

    public function test_quick_create_with_natural_deadline_and_time(): void
    {
        $user = User::factory()->create([
            'phone'    => '081234567890',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TODO besok ingatin jam 8 pagi mau sarapan',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Tugas baru berhasil dicatat', $response->json('reply'));
        $this->assertStringContainsString('Deadline:', $response->json('reply'));
        $this->assertStringContainsString('Pengingat Otomatis Aktif', $response->json('reply'));

        $todo = Todo::where('user_id', $user->id)->first();
        $this->assertNotNull($todo->deadline);
        $this->assertEquals(8, Carbon::parse($todo->deadline)->setTimezone('Asia/Jakarta')->hour);
    }

    public function test_quick_create_with_pipe_separator_and_priority(): void
    {
        $user = User::factory()->create([
            'phone'    => '081234567890',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TODO Makalah Fisika | besok 17:00 | tinggi',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Tugas baru berhasil dicatat', $response->json('reply'));
        $this->assertStringContainsString('Makalah Fisika', $response->json('reply'));
        $this->assertStringContainsString('Tinggi', $response->json('reply'));

        $todo = Todo::where('user_id', $user->id)->first();
        $this->assertEquals('Makalah Fisika', $todo->title);
        $this->assertEquals('high', $todo->priority);
        $this->assertNotNull($todo->deadline);
        $this->assertEquals(17, Carbon::parse($todo->deadline)->setTimezone('Asia/Jakarta')->hour);
    }

    public function test_list_command_shows_active_tasks(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);

        Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Membaca Buku Laravel',
            'status'      => 'pending',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TUGAS',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Daftar Tugas Aktif Kamu', $response->json('reply'));
        $this->assertStringContainsString('Membaca Buku Laravel', $response->json('reply'));
    }

    public function test_help_command_returns_guide(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'HELP',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Asisten WhatsApp', $response->json('reply'));
        $this->assertStringContainsString('DONE <id>', $response->json('reply'));
    }

    public function test_secret_token_protects_webhook_when_configured(): void
    {
        Config::set('services.fonnte.webhook_secret', 'secret123');

        // Request without secret should be 401
        $responseUnauthorized = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'HELP',
        ]);
        $responseUnauthorized->assertStatus(401);

        // Request with secret header should succeed
        $responseAuthorized = $this->withHeader('X-Webhook-Secret', 'secret123')
            ->postJson('/webhook/fonnte', []);
        $responseAuthorized->assertStatus(200);
    }

    public function test_morning_digest_includes_completed_tasks_and_priorities(): void
    {
        $user = User::factory()->create([
            'phone'    => '081234567890',
            'name'     => 'Sidiq Fatuh Rahman',
            'timezone' => 'Asia/Jakarta',
        ]);

        $completedTask = new Todo([
            'title'        => 'Sarapan Pagi',
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
        $completedTask->id = 101;

        $todayTask = new Todo([
            'title'    => 'Tugas Praktikum',
            'status'   => 'pending',
            'priority' => 'high',
            'deadline' => now()->setTime(15, 30),
        ]);
        $todayTask->id = 102;

        $notif = new \App\Notifications\MorningDigestNotification(
            collect([$todayTask]),
            collect(),
            collect([$completedTask])
        );

        $notif->toWhatsapp($user);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $msg = $request['message'];
            return str_contains($msg, 'Tugas yang Berhasil Kamu Selesaikan')
                && str_contains($msg, 'Sarapan Pagi')
                && str_contains($msg, 'Tugas Praktikum')
                && str_contains($msg, 'Tinggi')
                && str_contains($msg, '15:30');
        });
    }
}
