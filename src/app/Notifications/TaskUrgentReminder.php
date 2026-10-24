<?php

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TaskUrgentReminder extends Notification
{
    use Queueable;

    /**
     * @param Todo $todo
     * @param string $urgency '6_hours' or '1_hour'
     */
    public function __construct(
        public Todo $todo,
        public string $urgency
    ) {
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
        $label = $this->urgency === '1_hour' ? 'kurang dari 1 jam' : 'kurang dari 6 jam';

        return [
            'task_id' => $this->todo->id,
            'type'    => "urgent_{$this->urgency}",
            'urgency' => $this->urgency,
            'message' => "Task \"{$this->todo->title}\" deadline {$label} lagi!",
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

        if ($this->urgency === '1_hour') {
            $message = "🚨 *PERINGATAN KRITIS: 1 JAM LAGI!* 🚨\n\n"
                . "📌 *{$this->todo->title}*\n"
                . ($this->todo->description ? "📝 {$this->todo->description}\n" : '')
                . "🎯 Prioritas: {$priority}\n"
                . "⏰ Deadline: *{$deadline->format('H:i')} WIB (Kurang dari 60 menit)*\n\n"
                . "Waktu hampir habis! Segera selesaikan dan submit tugasmu sekarang juga! ⚡⚡\n\n"
                . "_— Wassup Task_";
        } else {
            $message = "⚠️ *PENGINGAT URGENT: DEADLINE MENDEKAT (< 6 JAM)*\n\n"
                . "📌 *{$this->todo->title}*\n"
                . ($this->todo->description ? "📝 {$this->todo->description}\n" : '')
                . "🎯 Prioritas: {$priority}\n"
                . "⏰ Deadline: *Hari ini, {$deadline->format('H:i')} WIB*\n\n"
                . "Waktu pengerjaan tersisa kurang dari 6 jam. Pastikan tugasmu segera difinalisasi ya! 💪\n\n"
                . "_— Wassup Task_";
        }

        Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->post(config('services.fonnte.url'), [
            'target'  => $notifiable->phone,
            'message' => $message,
        ]);
    }
}
