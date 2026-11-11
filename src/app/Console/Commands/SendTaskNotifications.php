<?php

namespace App\Console\Commands;

use App\Channels\WhatsappChannel;
use App\Models\ReminderDelivery;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\MorningDigestNotification;
use App\Notifications\TaskDeadlineReminder;
use App\Notifications\TaskH3Reminder;
use App\Notifications\TaskOverdue;
use App\Notifications\TaskUrgentReminder;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('app:send-task-notifications {--timezone=Asia/Jakarta : Default timezone}')]
#[Description('Create task reminders and synchronously retry pending WhatsApp deliveries')]
class SendTaskNotifications extends Command
{
    public function handle(): int
    {
        $defaultTz = $this->option('timezone') ?: 'Asia/Jakarta';
        $now = Carbon::now('Asia/Jakarta');

        User::where('is_banned', false)->chunkById(50, function ($users) use ($defaultTz, $now) {
            foreach ($users as $user) {
                $timezone = $user->timezone ?: $defaultTz;
                $localNow = $now->copy()->setTimezone($timezone);
                $today = $localNow->toDateString();
                $tomorrow = $localNow->copy()->addDay()->toDateString();
                $h3 = $localNow->copy()->addDays(3)->toDateString();
                $pending = $user->todos()->where('status', '!=', 'completed')->whereNotNull('deadline');

                if ($localNow->format('H:i') >= '07:00' && $localNow->format('H:i') <= '09:00') {
                    $tasks = (clone $pending)->whereBetween('deadline', [
                        $localNow->copy()->startOfDay()->setTimezone('Asia/Jakarta')->toDateTimeString(),
                        $localNow->copy()->addDay()->endOfDay()->setTimezone('Asia/Jakarta')->toDateTimeString(),
                    ])->orderBy('deadline')->get();
                    $todayTasks = $tasks->filter(fn ($todo) => Carbon::parse(ReminderDelivery::deadlineRevision($todo), 'Asia/Jakarta')->setTimezone($timezone)->toDateString() === $today);
                    $tomorrowTasks = $tasks->filter(fn ($todo) => Carbon::parse(ReminderDelivery::deadlineRevision($todo), 'Asia/Jakarta')->setTimezone($timezone)->toDateString() === $tomorrow);
                    $completed = $user->todos()->where('status', 'completed')->where('completed_at', '>=', $now->copy()->subDay()->toDateTimeString())->orderByDesc('completed_at')->limit(5)->get();

                    if ($tasks->isNotEmpty() || $completed->isNotEmpty()) {
                        $this->remember($user, new MorningDigestNotification($todayTasks, $tomorrowTasks, $completed), $today);
                    }
                }

                (clone $pending)->where('deadline', '<=', $localNow->copy()->addDays(3)->endOfDay()->setTimezone('Asia/Jakarta')->toDateTimeString())
                    ->chunkById(100, function ($todos) use ($user, $localNow, $timezone, $tomorrow, $h3, $now) {
                        foreach ($todos as $todo) {
                            $revision = ReminderDelivery::deadlineRevision($todo);
                            $deadline = Carbon::parse($revision, 'Asia/Jakarta')->setTimezone($timezone);
                            $minutesLeft = $now->diffInMinutes($deadline, false);

                            if ($deadline->toDateString() === $h3 && $localNow->hour >= 9) {
                                $this->remember($user, new TaskH3Reminder($todo), $revision, $todo);
                            }
                            if ($deadline->toDateString() === $tomorrow && $localNow->hour >= 17) {
                                $this->remember($user, new TaskDeadlineReminder($todo), $revision, $todo);
                            }
                            if ($minutesLeft > 0 && $minutesLeft <= 360) {
                                $urgency = $minutesLeft <= 60 ? '1_hour' : '6_hours';
                                $this->remember($user, new TaskUrgentReminder($todo, $urgency), $revision, $todo, $urgency);
                            } elseif ($minutesLeft < 0) {
                                $this->remember($user, new TaskOverdue($todo), $revision, $todo);
                            }
                        }
                    });
            }
        });

        $failures = $this->deliverPending();
        $this->info('Reminder inbox entries processed; WhatsApp delivery status is recorded separately.');

        return $failures ? self::FAILURE : self::SUCCESS;
    }

    private function remember(User $user, Notification $notification, string $revision, ?Todo $todo = null, string $urgency = ''): void
    {
        $identity = ReminderDelivery::identity($user->id, $notification::class, $todo?->id, $revision, $urgency);
        $id = (string) Str::uuid();
        $now = Carbon::now('Asia/Jakarta')->toDateTimeString();
        $data = $notification->toArray($user);
        if ($todo) {
            $data['deadline_revision'] = $revision;
        }

        DB::transaction(function () use ($user, $notification, $revision, $todo, $urgency, $identity, $id, $now, $data) {
            $inserted = DB::table('reminder_deliveries')->insertOrIgnore([
                'id' => $id,
                'identity' => $identity,
                'user_id' => $user->id,
                'task_id' => $todo?->id,
                'type' => $notification::class,
                'revision' => $revision,
                'urgency' => $urgency,
                'message' => $notification->toWhatsapp($user),
                'status' => $user->phone ? 'pending' : 'not_required',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if ($inserted) {
                DB::table('notifications')->insert([
                    'id' => $id,
                    'type' => $notification::class,
                    'notifiable_type' => $user->getMorphClass(),
                    'notifiable_id' => $user->id,
                    'data' => json_encode($data, JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function deliverPending(): int
    {
        $failures = 0;
        $now = Carbon::now('Asia/Jakarta');
        DB::table('reminder_deliveries')->where('status', 'processing')->where('attempts', '>=', 5)
            ->where('claimed_at', '<=', $now->copy()->subMinutes(10)->toDateTimeString())
            ->update(['status' => 'exhausted', 'claim_token' => null, 'updated_at' => $now->toDateTimeString()]);

        $this->claimable($now)->orderBy('id')->chunkById(100, function ($deliveries) use (&$failures) {
            foreach ($deliveries as $delivery) {
                $now = Carbon::now('Asia/Jakarta');
                $token = (string) Str::uuid();
                $claimed = $this->claimable($now)->where('id', $delivery->id)->update([
                    'status' => 'processing',
                    'claim_token' => $token,
                    'claimed_at' => $now->toDateTimeString(),
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => $now->toDateTimeString(),
                ]);
                if (!$claimed) {
                    continue;
                }

                $owned = DB::table('reminder_deliveries')->where('id', $delivery->id)->where('claim_token', $token);
                try {
                    $user = User::find($delivery->user_id);
                    $todo = $delivery->task_id ? Todo::find($delivery->task_id) : null;
                    $obsolete = $delivery->task_id
                        ? (!$todo || $todo->user_id != $delivery->user_id || $todo->status === 'completed' || !$todo->getRawOriginal('deadline') || ReminderDelivery::deadlineRevision($todo) !== $delivery->revision)
                        : ($delivery->revision !== $now->copy()->setTimezone($user?->timezone ?: 'Asia/Jakarta')->toDateString());
                    if (!$user || $user->is_banned || !$user->phone || $obsolete || ($delivery->type === TaskUrgentReminder::class && Carbon::parse($delivery->revision, 'Asia/Jakarta')->lte($now))) {
                        $owned->update(['status' => 'cancelled', 'claim_token' => null, 'updated_at' => $now->toDateTimeString()]);
                        continue;
                    }

                    app(WhatsappChannel::class)->sendMessage($user->phone, $delivery->message);
                    $owned->update(['status' => 'sent', 'sent_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(), 'last_error' => null, 'claim_token' => null, 'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString()]);
                } catch (Throwable $exception) {
                    $failures++;
                    $owned->update([
                        'status' => $delivery->attempts + 1 >= 5 ? 'exhausted' : 'failed',
                        'last_error' => $exception::class,
                        'next_attempt_at' => Carbon::now('Asia/Jakarta')->addMinutes(15)->toDateTimeString(),
                        'claim_token' => null,
                        'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                    ]);
                    $this->warn("WhatsApp delivery {$delivery->id} failed; recorded for retry.");
                }
            }
        }, 'id');

        return $failures;
    }

    private function claimable(Carbon $now): \Illuminate\Database\Query\Builder
    {
        return DB::table('reminder_deliveries')->where('attempts', '<', 5)
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now->toDateTimeString()))
            ->where(fn ($query) => $query->whereIn('status', ['pending', 'failed'])
                ->orWhere(fn ($stale) => $stale->where('status', 'processing')->where('claimed_at', '<=', $now->copy()->subMinutes(10)->toDateTimeString())));
    }
}
