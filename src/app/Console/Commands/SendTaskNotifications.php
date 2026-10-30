<?php

namespace App\Console\Commands;

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

#[Signature('app:send-task-notifications {--timezone=Asia/Jakarta : Default timezone}')]
#[Description('Send task morning digest, early warning, deadline reminders, urgent warnings, and overdue notifications')]
class SendTaskNotifications extends Command
{
    public function handle()
    {
        $defaultTz = $this->option('timezone') ?: 'Asia/Jakarta';

        $this->processMorningDigest($defaultTz);
        $this->processEarlyWarningH3($defaultTz);
        $this->processWarningH1($defaultTz);
        $this->processUrgentReminders($defaultTz);
        $this->processOverdueReminders($defaultTz);

        $this->info('Task notifications processed successfully.');
    }

    /**
     * 1. Morning Digest (Rangkuman Pagi jam 07:00 - 09:00)
     * Menggabungkan seluruh tugas hari ini dan besok dalam 1 pesan WA agar anti-spam.
     */
    protected function processMorningDigest(string $defaultTz): void
    {
        User::whereNotNull('phone')
            ->where('is_banned', false)
            ->chunk(50, function ($users) use ($defaultTz) {
                foreach ($users as $user) {
                    $timezone = $user->timezone ?? $defaultTz;
                    $userNow  = Carbon::now($timezone);
                    $hour     = (int) $userNow->format('H');

                    // Jendela Morning Digest: Jam 07:00 sampai 09:00 pagi waktu user
                    $timeStr = $userNow->format('H:i');
                    if ($timeStr < '07:00' || $timeStr > '09:00') {
                        continue;
                    }

                    $todayStr    = $userNow->toDateString();
                    $tomorrowStr = $userNow->copy()->addDay()->toDateString();

                    // Cek apakah user sudah menerima Morning Digest hari ini
                    $alreadySentToday = $user->notifications()
                        ->where('type', MorningDigestNotification::class)
                        ->whereJsonContains('data->date', $todayStr)
                        ->exists();

                    if ($alreadySentToday) {
                        continue;
                    }

                    // Ambil tugas yang belum selesai untuk hari ini & besok
                    $allPending = Todo::where('user_id', $user->id)
                        ->where('status', '!=', 'completed')
                        ->whereNotNull('deadline')
                        ->whereBetween('deadline', [
                            $userNow->copy()->subHours(12),
                            $userNow->copy()->addDays(2)->endOfDay(),
                        ])
                        ->orderBy('deadline', 'asc')
                        ->get();

                    $todayTasks = $allPending->filter(function ($todo) use ($timezone, $todayStr) {
                        return Carbon::parse($todo->deadline)->setTimezone($timezone)->toDateString() === $todayStr;
                    });

                    $tomorrowTasks = $allPending->filter(function ($todo) use ($timezone, $tomorrowStr) {
                        return Carbon::parse($todo->deadline)->setTimezone($timezone)->toDateString() === $tomorrowStr;
                    });
                    // Ambil tugas yang selesai dalam 24 jam terakhir
                    $recentCompleted = Todo::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->whereNotNull('completed_at')
                        ->where('completed_at', '>=', $userNow->copy()->subHours(24))
                        ->orderBy('completed_at', 'desc')
                        ->take(5)
                        ->get();

                    // Kirim jika ada tugas hari ini, besok, atau tugas yang baru selesai
                    if ($todayTasks->count() > 0 || $tomorrowTasks->count() > 0 || $recentCompleted->count() > 0) {
                        $user->notify(new MorningDigestNotification($todayTasks, $tomorrowTasks, $recentCompleted));
                        $this->info("Morning digest sent: [{$user->name}] ({$todayTasks->count()} today, {$tomorrowTasks->count()} tomorrow, {$recentCompleted->count()} completed)");
                    }
                }
            });
    }

    /**
     * 2. Pengingat H-3 (Early Warning)
     * Mengingatkan tugas besar / laporan 3 hari sebelum deadline.
     */
    protected function processEarlyWarningH3(string $defaultTz): void
    {
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now()->addDays(2)->startOfDay(), now()->addDays(4)->endOfDay()])
            ->chunk(100, function ($todos) use ($defaultTz) {
                foreach ($todos as $todo) {
                    $timezone       = $todo->user->timezone ?? $defaultTz;
                    $userNow        = Carbon::now($timezone);
                    $h3Date         = $userNow->copy()->addDays(3)->toDateString();
                    $deadlineUserTz = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    if ($deadlineUserTz->toDateString() !== $h3Date) {
                        continue;
                    }

                    // H-3 dikirim mulai jam 09:00 pagi waktu user agar tidak terkirim tengah malam & tidak tabrakan dengan Morning Digest
                    if ($userNow->hour < 9) {
                        continue;
                    }

                    $alreadyNotified = $todo->user->notifications()
                        ->where('type', TaskH3Reminder::class)
                        ->whereJsonContains('data->task_id', $todo->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $todo->user->notify(new TaskH3Reminder($todo));
                        $this->info("H-3 early warning sent: [{$todo->user->name}] {$todo->title}");
                    }
                }
            });
    }

    /**
     * 3. Pengingat H-1 (Warning)
     * Mengingatkan tugas yang jatuh tempo besok agar mulai dicicil.
     */
    protected function processWarningH1(string $defaultTz): void
    {
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now()->startOfDay(), now()->addDays(2)->endOfDay()])
            ->chunk(100, function ($todos) use ($defaultTz) {
                foreach ($todos as $todo) {
                    $timezone       = $todo->user->timezone ?? $defaultTz;
                    $userNow        = Carbon::now($timezone);
                    $tomorrow       = $userNow->copy()->addDay()->toDateString();
                    $deadlineUserTz = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    if ($deadlineUserTz->toDateString() !== $tomorrow) {
                        continue;
                    }

                    // H-1 dikirim mulai jam 17:00 (sore/malam) waktu user agar pengguna mulai mencicil malam itu
                    // dan tidak berulang dengan Morning Digest di pagi hari
                    if ($userNow->hour < 17) {
                        continue;
                    }

                    $alreadyNotified = $todo->user->notifications()
                        ->where('type', TaskDeadlineReminder::class)
                        ->whereJsonContains('data->task_id', $todo->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $todo->user->notify(new TaskDeadlineReminder($todo));
                        $this->info("H-1 warning sent: [{$todo->user->name}] {$todo->title}");
                    }
                }
            });
    }

    /**
     * 4. Pengingat Kritis (Urgent) — 6 Jam & 1 Jam Sebelum Deadline
     * Peringatan krusial bagi yang suka SKS atau lupa submit.
     */
    protected function processUrgentReminders(string $defaultTz): void
    {
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now(), now()->addHours(7)])
            ->chunk(100, function ($todos) use ($defaultTz) {
                foreach ($todos as $todo) {
                    $timezone       = $todo->user->timezone ?? $defaultTz;
                    $userNow        = Carbon::now($timezone);
                    $deadlineUserTz = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    $minutesLeft = $userNow->diffInMinutes($deadlineUserTz, false);

                    // Tahap A: 1 Jam Sebelum Deadline (0 < sisa menit <= 60)
                    if ($minutesLeft > 0 && $minutesLeft <= 60) {
                        $alreadyNotified = $todo->user->notifications()
                            ->where('type', TaskUrgentReminder::class)
                            ->whereJsonContains('data->task_id', $todo->id)
                            ->whereJsonContains('data->urgency', '1_hour')
                            ->exists();

                        if (!$alreadyNotified) {
                            $todo->user->notify(new TaskUrgentReminder($todo, '1_hour'));
                            $this->info("Urgent (1 Jam) sent: [{$todo->user->name}] {$todo->title}");
                        }
                    }
                    // Tahap B: 6 Jam Sebelum Deadline (60 < sisa menit <= 360)
                    elseif ($minutesLeft > 60 && $minutesLeft <= 360) {
                        $alreadyNotified = $todo->user->notifications()
                            ->where('type', TaskUrgentReminder::class)
                            ->whereJsonContains('data->task_id', $todo->id)
                            ->whereJsonContains('data->urgency', '6_hours')
                            ->exists();

                        if (!$alreadyNotified) {
                            $todo->user->notify(new TaskUrgentReminder($todo, '6_hours'));
                            $this->info("Urgent (6 Jam) sent: [{$todo->user->name}] {$todo->title}");
                        }
                    }
                }
            });
    }

    /**
     * 5. Pengingat Tugas Terlambat (Overdue)
     */
    protected function processOverdueReminders(string $defaultTz): void
    {
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->chunk(100, function ($todos) use ($defaultTz) {
                foreach ($todos as $todo) {
                    $timezone       = $todo->user->timezone ?? $defaultTz;
                    $userNow        = Carbon::now($timezone);
                    $deadlineUserTz = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    // Lewat deadline di timezone user
                    if ($deadlineUserTz >= $userNow) {
                        continue;
                    }

                    $alreadyNotified = $todo->user->notifications()
                        ->where('type', TaskOverdue::class)
                        ->whereJsonContains('data->task_id', $todo->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $todo->user->notify(new TaskOverdue($todo));
                        $this->info("Overdue notif sent: [{$todo->user->name}] {$todo->title}");
                    }
                }
            });
    }
}
