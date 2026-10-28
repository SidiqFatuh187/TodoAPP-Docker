<?php

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MorningDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Collection $todayTasks,
        public Collection $tomorrowTasks
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
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $todayStr = Carbon::now($timezone)->toDateString();

        return [
            'type'           => 'morning_digest',
            'date'           => $todayStr,
            'today_count'    => $this->todayTasks->count(),
            'tomorrow_count' => $this->tomorrowTasks->count(),
            'message'        => "Rangkuman Pagi: {$this->todayTasks->count()} tugas hari ini dan {$this->tomorrowTasks->count()} tugas besok.",
        ];
    }

    public function toWhatsapp(object $notifiable): void
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $userNow  = Carbon::now($timezone);

        $todayCount    = $this->todayTasks->count();
        $tomorrowCount = $this->tomorrowTasks->count();

        $message = "Selamat pagi *{$notifiable->name}*! ☀️\n\n"
            . "📋 *Rangkuman Tugas Kamu Hari Ini*\n"
            . "📅 Tanggal: *{$userNow->translatedFormat('l, d F Y')}*\n\n"
            . "Kamu punya *{$todayCount} tugas hari ini* dan *{$tomorrowCount} tugas besok*:\n\n";

        if ($todayCount > 0) {
            $message .= "📅 *Hari Ini:*\n";
            $i = 1;
            foreach ($this->todayTasks as $todo) {
                $deadline = Carbon::parse($todo->deadline)->setTimezone($timezone);
                $priority = match ($todo->priority) {
                    'high'   => '🔴',
                    'medium' => '🟡',
                    'low'    => '🟢',
                    default  => '⚪',
                };
                $message .= "{$i}. {$priority} [ID: #{$todo->id}] *{$todo->title}* — Jam {$deadline->format('H:i')}\n";
                $i++;
            }
            $message .= "\n";
        }

        if ($tomorrowCount > 0) {
            $message .= "⏰ *Besok:*\n";
            $i = 1;
            foreach ($this->tomorrowTasks as $todo) {
                $deadline = Carbon::parse($todo->deadline)->setTimezone($timezone);
                $priority = match ($todo->priority) {
                    'high'   => '🔴',
                    'medium' => '🟡',
                    'low'    => '🟢',
                    default  => '⚪',
                };
                $message .= "{$i}. {$priority} [ID: #{$todo->id}] *{$todo->title}* — Jam {$deadline->format('H:i')}\n";
                $i++;
            }
            $message .= "\n";
        }

        $message .= "Semangat produktif hari ini! Jangan lupa diselesaikan ya 💪\n\n"
            . "💡 *Tips:* Balas *DONE <id>* untuk menandai tugas selesai langsung dari chat ini!\n\n"
            . "_— Wassup Task_";
        Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->post(config('services.fonnte.url'), [
            'target'  => $notifiable->phone,
            'message' => $message,
        ]);
    }
}

