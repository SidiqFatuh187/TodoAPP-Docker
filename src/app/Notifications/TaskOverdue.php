<?php

namespace App\Notifications;

use App\Models\Todo;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;
use App\Channels\WhatsappChannel;

class TaskOverdue extends Notification
{
    public function __construct(public Todo $todo)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = $notifiable->is_banned ? [] : ['database'];

        if (!$notifiable->is_banned && $notifiable->phone) {
            $channels[] = WhatsappChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
       return [
            'message' => "Task \"{$this->todo->title}\" sudah melewati deadline.",
            'task_id' => $this->todo->id,
            'type'    => 'overdue',
        ];
    }

    public function toWhatsapp(object $notifiable): string
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $deadline = Carbon::parse($this->todo->getRawOriginal('deadline'), 'Asia/Jakarta')->setTimezone($timezone);

        $priority = match($this->todo->priority) {
            'high'   => '🔴 Tinggi',
            'medium' => '🟡 Sedang',
            'low'    => '🟢 Rendah',
            default  => '-',
        };

        $message = "Halo *{$notifiable->name}*! 👋\n\n"
        . "🚨 *Task Telat Deadline!*\n\n"
        . "📌 *{$this->todo->title}* [ID: #{$this->todo->id}]\n"
        . "📅 Deadline: *{$deadline->format('d M Y, H:i')} {$timezone}*\n\n"
        . "Task ini sudah melewati deadline. Segera selesaikan! ⚡\n"
        . "👉 Balas *DONE {$this->todo->id}* jika sudah selesai!\n\n"
        . "_— Wassup Task_";

        return $message;
    }
}
