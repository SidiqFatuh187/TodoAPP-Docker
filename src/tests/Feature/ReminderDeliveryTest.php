<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use App\Notifications\MorningDigestNotification;
use App\Notifications\TaskOverdue;
use App\Notifications\TaskUrgentReminder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReminderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Asia/Jakarta', 'services.fonnte.url' => 'https://fonnte.test/send', 'services.fonnte.token' => 'test-token']);
        $this->travelTo(Carbon::parse('2026-11-11 12:00:00', 'Asia/Jakarta'));
    }

    private function task(array $userAttributes = [], string $deadline = '2026-11-11 11:00:00'): Todo
    {
        $user = User::factory()->create(array_merge(['phone' => '628123456789', 'timezone' => 'Asia/Jakarta', 'is_banned' => false], $userAttributes));

        return Todo::create(['user_id' => $user->id, 'title' => 'Reminder regression', 'status' => 'pending', 'deadline' => $deadline]);
    }

    public function test_business_failure_retries_without_duplicate_inbox_or_false_success(): void
    {
        $todo = $this->task();
        Http::fakeSequence()->push(['status' => false], 200)->push(['status' => true], 200);

        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'failed', 'attempts' => 1, 'sent_at' => null]);
        $this->assertDatabaseCount('notifications', 1);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);

        $this->travel(15)->minutes();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'sent', 'attempts' => 2]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_http_failure_isolated_from_other_recipients_and_retries_later(): void
    {
        $first = $this->task(['phone' => '628111111111']);
        $second = $this->task(['phone' => '628222222222']);
        $failFirst = true;
        Http::fake(function ($request) use (&$failFirst) {
            return Http::response(['status' => true], $failFirst && $request['target'] === '628111111111' ? 503 : 200);
        });

        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $first->id, 'status' => 'failed', 'sent_at' => null]);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $second->id, 'status' => 'sent']);
        $this->assertDatabaseCount('notifications', 2);

        $failFirst = false;
        $this->travel(15)->minutes();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $first->id, 'status' => 'sent', 'attempts' => 2]);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_deleted_inbox_does_not_resend_but_changed_deadline_is_new_identity(): void
    {
        $todo = $this->task();
        Http::fake(['*' => Http::response(['status' => true])]);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        $todo->user->notifications()->delete();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('notifications', 0);

        $todo->update(['deadline' => '2026-11-11 10:30:00']);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(2);
        $this->assertDatabaseCount('reminder_deliveries', 2);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_connection_failure_does_not_abort_other_recipients(): void
    {
        $first = $this->task(['phone' => '628111111111']);
        $second = $this->task(['phone' => '628222222222']);
        Http::fake(function ($request) {
            if ($request['target'] === '628111111111') {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            }

            return Http::response(['status' => true]);
        });
        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $first->id, 'status' => 'failed']);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $second->id, 'status' => 'sent']);
    }

    public function test_changed_deadline_cancels_failed_old_revision_before_retry(): void
    {
        $todo = $this->task();
        Http::fakeSequence()->push(['status' => false])->push(['status' => true]);
        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        $todo->update(['deadline' => '2026-11-20 12:00:00']);
        $this->travel(15)->minutes();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'cancelled']);
    }

    public function test_banned_users_receive_neither_new_inbox_nor_pending_whatsapp(): void
    {
        $banned = $this->task(['is_banned' => true]);
        $pending = $this->task();
        Http::fake(['*' => Http::response(['status' => false])]);
        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        $this->assertDatabaseMissing('reminder_deliveries', ['user_id' => $banned->user_id]);
        $pending->user->update(['is_banned' => true]);

        $this->travel(15)->minutes();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $pending->id, 'status' => 'cancelled']);
        $banned->user->notify(new TaskOverdue($banned));
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_claim_blocks_competing_run_and_stale_claim_recovers(): void
    {
        $todo = $this->task();
        Http::fakeSequence()->push(['status' => false])->push(['status' => true]);
        $this->artisan('app:send-task-notifications')->assertExitCode(1);
        DB::table('reminder_deliveries')->update(['status' => 'processing', 'claim_token' => (string) Str::uuid(), 'claimed_at' => now()->toDateTimeString(), 'next_attempt_at' => null]);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);

        $this->travel(11)->minutes();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'sent', 'attempts' => 2]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_competing_command_cannot_send_an_in_flight_delivery(): void
    {
        $todo = $this->task();
        $competed = false;
        Http::fake(function () use (&$competed) {
            if (!$competed) {
                $competed = true;
                $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('app:send-task-notifications'));
            }

            return Http::response(['status' => true]);
        });
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'sent', 'attempts' => 1]);
    }

    public function test_retries_stop_after_five_failed_attempts(): void
    {
        $todo = $this->task();
        Http::fake(['*' => Http::response(['status' => false])]);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->artisan('app:send-task-notifications')->assertExitCode(1);
            $this->travel(15)->minutes();
        }
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(5);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'status' => 'exhausted', 'attempts' => 5, 'sent_at' => null]);
    }

    public function test_canonical_deadline_selects_and_displays_correct_instant_for_wit(): void
    {
        $todo = $this->task(['timezone' => 'Asia/Jayapura'], '2026-11-11 12:30:00');
        Http::fake(['*' => Http::response(['status' => true])]);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);

        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $todo->id, 'type' => TaskUrgentReminder::class, 'urgency' => '1_hour', 'revision' => '2026-11-11 12:30:00', 'status' => 'sent']);
        Http::assertSent(fn ($request) => str_contains($request['message'], '14:30') && str_contains($request['message'], 'Asia/Jayapura') && !str_contains($request['message'], 'WIB'));
    }

    public function test_digest_uses_local_date_boundaries_and_keeps_dedupe_after_inbox_deletion(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 05:30:00', 'Asia/Jakarta'));
        $todo = $this->task(['timezone' => 'Asia/Jayapura'], '2026-11-10 23:00:00');
        Http::fake(['*' => Http::response(['status' => true])]);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        $digest = DB::table('notifications')->where('type', MorningDigestNotification::class)->first();
        $this->assertNotNull($digest);
        $this->assertSame(1, json_decode($digest->data, true)['today_count']);
        $this->assertSame('2026-11-11', json_decode($digest->data, true)['date']);
        Http::assertSent(fn ($request) => str_contains($request['message'], '01:00 Asia/Jayapura'));
        $count = DB::table('reminder_deliveries')->count();
        $todo->user->notifications()->delete();
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        $this->assertDatabaseCount('reminder_deliveries', $count);
        $this->assertDatabaseCount('notifications', 0);
        Http::assertSentCount($count);
    }

    public function test_legacy_history_seeds_unknown_without_claiming_sent_or_suppressing_revised_task(): void
    {
        $unchanged = $this->task();
        $changed = $this->task();
        foreach ([$unchanged, $changed] as $todo) {
            $todo->user->notifyNow(new TaskOverdue($todo), ['database']);
        }
        $this->travel(1)->minutes();
        $changed->update(['deadline' => '2026-11-11 10:00:00']);
        Schema::drop('reminder_deliveries');
        $migration = require database_path('migrations/2026_11_11_000001_create_reminder_deliveries_table.php');
        $migration->up();
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $unchanged->id, 'status' => 'legacy_unknown', 'sent_at' => null]);
        $this->assertDatabaseMissing('reminder_deliveries', ['task_id' => $changed->id]);

        Http::fake(['*' => Http::response(['status' => true])]);
        $this->artisan('app:send-task-notifications')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('reminder_deliveries', ['task_id' => $changed->id, 'status' => 'sent']);
    }
}
