<?php

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TaskH3Reminder extends Notification
{
    use Queueable;

    public function __construct(public Todo $todo)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->phone) {
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

    public function toWhatsapp(object $notifiable): void
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $deadline = Carbon::parse($this->todo->deadline)->setTimezone($timezone);

        $priority = match ($this->todo->priority) {
            'high'   => '🔴 Tinggi',
            'medium' => '🟡 Sedang',
            'low'    => '🟢 Rendah',
            default  => '-',
        };

        $message = "Halo *{$notifiable->name}*! 👋\n\n"
            . "⏳ *Pengingat Awal (H-3 Deadline)*\n\n"
            . "📌 *{$this->todo->title}*\n"
            . ($this->todo->description ? "📝 {$this->todo->description}\n" : '')
            . "🎯 Prioritas: {$priority}\n"
            . "📅 Deadline: *{$deadline->translatedFormat('l, d M Y — H:i')} WIB*\n\n"
            . "Tugas ini punya batas waktu 3 hari lagi. Yuk mulai dicicil pengerjaannya dari sekarang! 🚀\n\n"
            . "_— Wassup Task_";

        Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->post(config('services.fonnte.url'), [
            'target'  => $notifiable->phone,
            'message' => $message,
        ]);
    }
}

