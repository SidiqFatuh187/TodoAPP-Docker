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
        Config::set('services.fonnte.webhook_secret', 'test-webhook-secret');
        Config::set('services.fonnte.token', 'test-token');
        Config::set('services.fonnte.url', 'https://api.fonnte.com/send');
        $this->withHeader('X-Webhook-Secret', 'test-webhook-secret');
        Http::fake(['*' => Http::response(['status' => true])]);
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

        foreach (["DONE {$todoB->id}", "TUNDA {$todoB->id} 2h", "HAPUS {$todoB->id}"] as $command) {
            $response = $this->postJson('/webhook/fonnte', [
                'sender'  => '6281234567801',
                'message' => $command,
            ]);

            $response->assertStatus(200);
            $this->assertStringContainsString('tidak ditemukan atau bukan milik kamu', $response->json('reply'));
            $this->assertSame('pending', $todoB->fresh()->status);
        }
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
        $responseUnauthorized = $this->withoutHeader('X-Webhook-Secret')->postJson('/webhook/fonnte', [
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
        $completedTask->syncOriginal();

        $todayTask = new Todo([
            'title'    => 'Tugas Praktikum',
            'status'   => 'pending',
            'priority' => 'high',
            'deadline' => now()->setTime(15, 30),
        ]);
        $todayTask->id = 102;
        $todayTask->syncOriginal();

        $notif = new \App\Notifications\MorningDigestNotification(
            collect([$todayTask]),
            collect(),
            collect([$completedTask])
        );

        (new \App\Channels\WhatsappChannel())->send($user, $notif);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $msg = $request['message'];
            return str_contains($msg, 'Sarapan Pagi')
                && str_contains($msg, 'Tugas Praktikum')
                && str_contains($msg, 'Tinggi')
                && str_contains($msg, '15:30');
        });
    }

    public function test_delete_command_deletes_task(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);
        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Tugas Salah Ketik',
            'status'      => 'pending',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => "HAPUS {$todo->id}",
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('berhasil dihapus', $response->json('reply'));
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_search_command_finds_task(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Kuliah',
            'color'   => '#3b82f6',
            'icon'    => 'book',
        ]);
        Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Laporan Praktikum Fisika',
            'status'      => 'pending',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'CARI praktikum',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Hasil Pencarian', $response->json('reply'));
        $this->assertStringContainsString('Laporan Praktikum Fisika', $response->json('reply'));
    }

    public function test_rekap_command_returns_summary(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Umum',
            'color'   => '#6366f1',
            'icon'    => 'tasks',
        ]);
        Todo::create([
            'user_id'      => $user->id,
            'category_id'  => $category->id,
            'title'        => 'Tugas Sudah Selesai',
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
        Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Tugas Masih Pending',
            'status'      => 'pending',
            'priority'    => 'high',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'REKAP',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Rekap Progres Tugas Kamu', $response->json('reply'));
        $this->assertStringContainsString('Selesai Hari Ini: *1 tugas*', $response->json('reply'));
        $this->assertStringContainsString('Masih Aktif / Pending: *1 tugas*', $response->json('reply'));
        $this->assertStringContainsString('Prioritas Tinggi: *1 tugas*', $response->json('reply'));
    }

    public function test_quick_create_with_custom_category(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Pribadi',
            'color'   => '#10b981',
            'icon'    => 'user',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender'  => '6281234567890',
            'message' => 'TODO Beli vitamin | besok 20:00 | Pribadi | tinggi',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Tugas baru berhasil dicatat', $response->json('reply'));
        $this->assertStringContainsString('Pribadi', $response->json('reply'));

        $todo = Todo::where('user_id', $user->id)->first();
        $this->assertEquals($category->id, $todo->category_id);
        $this->assertEquals('high', $todo->priority);
    }

    public function test_webhook_fails_closed_without_a_configured_secret(): void
    {
        User::factory()->create(['phone' => '081234567890']);

        foreach ([null, ''] as $secret) {
            Config::set('services.fonnte.webhook_secret', $secret);
            $this->postJson('/webhook/fonnte', [
                'sender' => '6281234567890',
                'message' => 'TODO Unauthorized task',
            ])->assertUnauthorized();
        }

        $this->assertDatabaseCount('todos', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        Http::assertNothingSent();
        $this->getJson('/webhook/fonnte')->assertOk()->assertJson(['status' => 'active']);
    }

    public function test_invalid_or_missing_secret_cannot_create_tasks(): void
    {
        User::factory()->create(['phone' => '081234567890']);
        $payload = ['sender' => '6281234567890', 'message' => 'TODO Unauthorized task'];

        $this->withoutHeader('X-Webhook-Secret')->postJson('/webhook/fonnte', $payload)->assertUnauthorized();
        $this->withHeader('X-Webhook-Secret', 'wrong')->postJson('/webhook/fonnte', $payload)->assertUnauthorized();
        $this->withoutHeader('X-Webhook-Secret')
            ->postJson('/webhook/fonnte', $payload + ['secret' => ['test-webhook-secret']])
            ->assertUnauthorized();

        $this->assertDatabaseCount('todos', 0);
        Http::assertNothingSent();
    }

    public function test_formatted_phone_and_sender_suffix_match_the_account(): void
    {
        $user = User::factory()->create(['phone' => '+62 (812) 3456-7890']);

        $this->postJson('/webhook/fonnte', [
            'sender' => '081234567890@c.us',
            'message' => 'TODO Own task',
        ])->assertOk();

        $this->assertDatabaseHas('todos', ['user_id' => $user->id, 'title' => 'Own task']);
    }

    public function test_ambiguous_equivalent_phone_numbers_cannot_mutate_tasks(): void
    {
        User::factory()->create(['phone' => '081234567890']);
        User::factory()->create(['phone' => '+62 (812) 3456-7890']);

        $this->postJson('/webhook/fonnte', [
            'sender' => '6281234567890',
            'message' => 'TODO Must not select newest account',
        ])->assertOk();

        $this->assertDatabaseCount('todos', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_local_create_deadlines_are_stored_canonically_and_localized_in_replies(): void
    {
        $user = User::factory()->create(['phone' => '081234567890', 'timezone' => 'Asia/Makassar']);

        $this->travelTo(Carbon::parse('2026-11-11 12:00:00', 'Asia/Jakarta'), function () use ($user) {
            foreach ([
                'TODO Besok pagi sarapan kategori Pribadi tinggi',
                'TODO Sarapan | besok pagi | Pribadi | tinggi',
            ] as $command) {
                $response = $this->postJson('/webhook/fonnte', [
                    'sender' => '6281234567890',
                    'message' => $command,
                ])->assertOk();

                $todo = Todo::where('user_id', $user->id)->latest('id')->firstOrFail();
                $this->assertSame('Sarapan', $todo->title);
                $this->assertSame('high', $todo->priority);
                $this->assertSame('Pribadi', $todo->category->name);
                $this->assertSame('2026-11-12 07:00:00', $todo->getRawOriginal('deadline'));
                $this->assertStringContainsString('08:00', $response->json('reply'));
                $this->assertStringContainsString('Asia/Makassar', $response->json('reply'));
            }

            foreach (['TUGAS', 'CARI Sarapan'] as $command) {
                $response = $this->postJson('/webhook/fonnte', [
                    'sender' => '6281234567890',
                    'message' => $command,
                ])->assertOk();
                $this->assertStringContainsString('08:00', $response->json('reply'));
            }
        });
    }

    public function test_snooze_uses_canonical_existing_deadline_and_local_now_for_expired_tasks(): void
    {
        $user = User::factory()->create(['phone' => '081234567890', 'timezone' => 'Asia/Makassar']);
        $category = Category::create(['user_id' => $user->id, 'name' => 'Umum', 'color' => '#6366f1', 'icon' => 'tasks']);

        $this->travelTo(Carbon::parse('2026-11-11 12:00:00', 'Asia/Jakarta'), function () use ($user, $category) {
            foreach ([
                ['2026-11-11 14:00:00', '2026-11-11 16:00:00', '17:00'],
                ['2026-11-11 10:00:00', '2026-11-11 14:00:00', '15:00'],
                [null, '2026-11-11 14:00:00', '15:00'],
            ] as [$deadline, $expectedStored, $expectedLocal]) {
                $todo = Todo::create([
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'title' => 'Snooze task',
                    'status' => 'pending',
                    'deadline' => $deadline,
                ]);

                $response = $this->postJson('/webhook/fonnte', [
                    'sender' => '6281234567890',
                    'message' => "TUNDA {$todo->id} 2h",
                ])->assertOk();

                $this->assertSame($expectedStored, $todo->fresh()->getRawOriginal('deadline'));
                $this->assertStringContainsString($expectedLocal, $response->json('reply'));
                $this->assertStringContainsString('Asia/Makassar', $response->json('reply'));
            }
        });
    }

    public function test_body_and_query_secrets_keep_the_existing_authentication_contract(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $this->withoutHeader('X-Webhook-Secret');

        $this->postJson('/webhook/fonnte', [
            'sender' => '6281234567890',
            'message' => 'TODO Body secret task',
            'secret' => 'test-webhook-secret',
        ])->assertOk();

        $this->postJson('/webhook/fonnte?secret=test-webhook-secret', [
            'sender' => '6281234567890',
            'message' => 'TODO Query secret task',
        ])->assertOk();

        $this->assertSame(2, Todo::where('user_id', $user->id)->count());
    }

    public function test_request_secrets_and_message_content_are_not_logged(): void
    {
        User::factory()->create(['phone' => '081234567890']);
        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('Fonnte reply dispatched.', ['status' => 200]);

        $this->postJson('/webhook/fonnte', [
            'sender' => '6281234567890',
            'message' => 'TODO Private message content',
            'secret' => 'test-webhook-secret',
        ])->assertOk();
    }
}
