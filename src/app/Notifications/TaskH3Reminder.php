<?php

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class TaskH3Reminder extends Notification
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
            'task_id' => $this->todo->id,
            'type'    => 'h3_reminder',
            'message' => "Task \"{$this->todo->title}\" deadline dalam 3 hari.",
        ];
    }

    public function toWhatsapp(object $notifiable): string
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $deadline = Carbon::parse($this->todo->getRawOriginal('deadline'), 'Asia/Jakarta')->setTimezone($timezone);

        $priority = match ($this->todo->priority) {
            'high'   => '🔴 Tinggi',
            'medium' => '🟡 Sedang',
            'low'    => '🟢 Rendah',
            default  => '-',
        };

        $message = "Halo *{$notifiable->name}*! 👋\n\n"
            . "⏳ *Pengingat Awal (H-3 Deadline)*\n\n"
            . "📌 *{$this->todo->title}* [ID: #{$this->todo->id}]\n"
            . ($this->todo->description ? "📝 {$this->todo->description}\n" : '')
            . "🎯 Prioritas: {$priority}\n"
            . "📅 Deadline: *{$deadline->translatedFormat('l, d M Y, H:i')} {$timezone}*\n\n"
            . "Tugas ini punya batas waktu 3 hari lagi. Yuk mulai dicicil pengerjaannya dari sekarang! 🚀\n"
            . "👉 Balas pesan ini: *DONE {$this->todo->id}* jika sudah selesai!\n\n"
            . "_— Wassup Task_";

        return $message;
    }
}

