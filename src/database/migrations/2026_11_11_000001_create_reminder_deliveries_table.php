<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identity', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Keep task identity after deletion; deleting an inbox entry must not erase delivery history.
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('type');
            $table->string('revision');
            $table->string('urgency')->default('');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at']);
        });

        $types = [
            'App\\Notifications\\MorningDigestNotification',
            'App\\Notifications\\TaskH3Reminder',
            'App\\Notifications\\TaskDeadlineReminder',
            'App\\Notifications\\TaskDueTodayReminder',
            'App\\Notifications\\TaskUrgentReminder',
            'App\\Notifications\\TaskOverdue',
        ];
        DB::table('notifications')->where('notifiable_type', 'App\\Models\\User')->whereIn('type', $types)->orderBy('id')->chunkById(200, function ($notifications) {
            foreach ($notifications as $notification) {
                $data = json_decode($notification->data, true);
                if (!is_array($data) || !DB::table('users')->where('id', $notification->notifiable_id)->exists()) {
                    continue;
                }
                $taskId = isset($data['task_id']) ? (int) $data['task_id'] : null;
                if ($taskId) {
                    $task = DB::table('todos')->where('id', $taskId)->where('user_id', $notification->notifiable_id)->first();
                    if (!$task || !$task->deadline) {
                        continue;
                    }
                    // Old inbox rows lack deadline revisions: only seed an unchanged task's current deadline.
                    if (!isset($data['deadline_revision']) && $task->updated_at > $notification->created_at) {
                        continue;
                    }
                    $revision = Carbon::parse($data['deadline_revision'] ?? $task->deadline, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                } else {
                    $revision = $data['date'] ?? null;
                    if (!$revision || $notification->type !== 'App\\Notifications\\MorningDigestNotification') {
                        continue;
                    }
                }
                $urgency = $data['urgency'] ?? '';
                $identity = hash('sha256', json_encode([(int) $notification->notifiable_id, $notification->type, $taskId, $revision, $urgency], JSON_THROW_ON_ERROR));
                DB::table('reminder_deliveries')->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'identity' => $identity,
                    'user_id' => $notification->notifiable_id,
                    'task_id' => $taskId,
                    'type' => $notification->type,
                    'revision' => $revision,
                    'urgency' => $urgency,
                    // An old inbox row cannot prove that Fonnte accepted a message.
                    'status' => 'legacy_unknown',
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                ]);
            }
        }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');
    }
};
