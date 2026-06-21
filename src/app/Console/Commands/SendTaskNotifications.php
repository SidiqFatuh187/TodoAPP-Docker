<?php

namespace App\Console\Commands;

use App\Models\Todo;
use App\Notifications\TaskOverdue;
use App\Notifications\TaskDeadlineReminder;
use App\Notifications\TaskDueTodayReminder;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Carbon\Carbon;

#[Signature('app:send-task-notifications')]
#[Description('Send task overdue, due today, and deadline reminder notifications')]
class SendTaskNotifications extends Command
{
    public function handle()
    {
        // Overdue 
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->where('deadline', '<', now()->startOfDay())
            ->chunk(100, function ($todos) {
                foreach ($todos as $todo) {
                    $timezone         = $todo->user->timezone ?? 'Asia/Jakarta';
                    $userNow          = Carbon::now($timezone);
                    $deadlineUserTz   = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    // Skip kalau deadline belum lewat di timezone user
                    if ($deadlineUserTz->toDateString() >= $userNow->toDateString()) {
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

        // Due Today 
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->chunk(100, function ($todos) {
                foreach ($todos as $todo) {
                    $timezone         = $todo->user->timezone ?? 'Asia/Jakarta';
                    $userNow          = Carbon::now($timezone);
                    $deadlineUserTz   = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    // Skip kalau bukan hari ini di timezone user
                    if ($deadlineUserTz->toDateString() !== $userNow->toDateString()) {
                        continue;
                    }

                    $alreadyNotified = $todo->user->notifications()
                        ->where('type', TaskDueTodayReminder::class)
                        ->whereJsonContains('data->task_id', $todo->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $todo->user->notify(new TaskDueTodayReminder($todo));
                        $this->info("Due today notif sent: [{$todo->user->name}] {$todo->title}");
                    }
                }
            });

        // Deadline Reminder (H-1) ─
        Todo::with('user')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->chunk(100, function ($todos) {
                foreach ($todos as $todo) {
                    $timezone         = $todo->user->timezone ?? 'Asia/Jakarta';
                    $userNow          = Carbon::now($timezone);
                    $tomorrow         = $userNow->copy()->addDay()->toDateString();
                    $deadlineUserTz   = Carbon::parse($todo->deadline)->setTimezone($timezone);

                    // Skip kalau bukan besok di timezone user
                    if ($deadlineUserTz->toDateString() !== $tomorrow) {
                        continue;
                    }

                    $alreadyNotified = $todo->user->notifications()
                        ->where('type', TaskDeadlineReminder::class)
                        ->whereJsonContains('data->task_id', $todo->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $todo->user->notify(new TaskDeadlineReminder($todo));
                        $this->info("Reminder notif sent: [{$todo->user->name}] {$todo->title}");
                    }
                }
            });

        $this->info('Task notifications sent successfully.');
    }
}