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
        public Collection $tomorrowTasks,
        public ?Collection $completedTasks = null
    ) {
        $this->completedTasks = $completedTasks ?? collect();
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
        $completedCount = $this->completedTasks->count();

        return [
            'type'            => 'morning_digest',
            'date'            => $todayStr,
            'today_count'     => $this->todayTasks->count(),
            'tomorrow_count'  => $this->tomorrowTasks->count(),
            'completed_count' => $completedCount,
            'message'         => "Rangkuman Pagi: {$completedCount} tugas diselesaikan, {$this->todayTasks->count()} hari ini, {$this->tomorrowTasks->count()} besok.",
        ];
    }

    public function toWhatsapp(object $notifiable): void
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $userNow  = Carbon::now($timezone);

        $todayCount     = $this->todayTasks->count();
        $tomorrowCount  = $this->tomorrowTasks->count();
        $completedCount = $this->completedTasks->count();

        $message = "Selamat pagi *{$notifiable->name}*! ☀️\n\n"
            . "📊 *Rangkuman Produktivitas Pagi Ini*\n"
            . "📅 Hari ini: *{$userNow->translatedFormat('l, d F Y')}*\n\n";

        // Section 1: Tugas yang sudah diselesaikan dalam 24 jam terakhir
        if ($completedCount > 0) {
            $message .= "🎉 *Tugas yang Berhasil Kamu Selesaikan ({$completedCount}):*\n";
            $i = 1;
            foreach ($this->completedTasks as $todo) {
                $completedTime = $todo->completed_at 
                    ? Carbon::parse($todo->completed_at)->setTimezone($timezone)->translatedFormat('H:i')
                    : '';
                $timeSuffix = $completedTime ? " (Selesai jam {$completedTime})" : '';
                $message .= "{$i}. ✅ *{$todo->title}*{$timeSuffix}\n";
                $i++;
            }
            $message .= "\n";
        }

        // Section 2: Jadwal Tugas Mendatang
        if ($todayCount > 0 || $tomorrowCount > 0) {
            $message .= "📋 *Tugas Selanjutnya yang Perlu Diselesaikan:*\n\n";

            if ($todayCount > 0) {
                $message .= "📅 *Hari Ini ({$todayCount} Tugas):*\n";
                $i = 1;
                foreach ($this->todayTasks as $todo) {
                    $deadline = Carbon::parse($todo->deadline)->setTimezone($timezone);
                    $priority = match ($todo->priority) {
                        'high'   => '🔴 *Tinggi*',
                        'low'    => '🟢 *Rendah*',
                        default  => '🟡 *Sedang*',
                    };
                    $message .= "{$i}. [ID: #{$todo->id}] *{$todo->title}*\n"
                              . "   ⏰ Deadline: *Jam {$deadline->format('H:i')} WIB*\n"
                              . "   🎯 Prioritas: {$priority}\n";
                    $i++;
                }
                $message .= "\n";
            }

            if ($tomorrowCount > 0) {
                $message .= "⏰ *Besok ({$tomorrowCount} Tugas):*\n";
                $i = 1;
                foreach ($this->tomorrowTasks as $todo) {
                    $deadline = Carbon::parse($todo->deadline)->setTimezone($timezone);
                    $priority = match ($todo->priority) {
                        'high'   => '🔴 *Tinggi*',
                        'low'    => '🟢 *Rendah*',
                        default  => '🟡 *Sedang*',
                    };
                    $message .= "{$i}. [ID: #{$todo->id}] *{$todo->title}*\n"
                              . "   ⏰ Deadline: *Jam {$deadline->format('H:i')} WIB*\n"
                              . "   🎯 Prioritas: {$priority}\n";
                    $i++;
                }
                $message .= "\n";
            }
        } else {
            $message .= "✨ *Tidak ada tugas aktif untuk hari ini dan besok.* Nikmati harimu dengan produktif!\n\n";
        }

        $message .= "💪 Semangat beraktivitas!\n"
            . "👉 Balas *DONE <id>* untuk menandai tugas selesai langsung dari chat ini.\n\n"
            . "_— Wassup Task_";

        Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->post(config('services.fonnte.url'), [
            'target'  => $notifiable->phone,
            'message' => $message,
        ]);
    }
}

