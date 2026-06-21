<?php

namespace App\Notifications;

use App\Models\Todo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Channels\WhatsappChannel;

class TaskDueTodayReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Todo $todo)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->phone) {
            $channels[] = WhatsappChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toArray(object $notifiable): array 
    {
         return [
            'message' => "Task \"{$this->todo->title}\" deadline hari ini.",
            'task_id' => $this->todo->id,
            'type'    => 'due_today',
        ];
    }

     public function toWhatsapp(object $notifiable): void
    {
        $timezone = $notifiable->timezone ?? 'Asia/Jakarta';
        $deadline = Carbon::parse($this->todo->deadline)->setTimezone($timezone);

        $priority = match($this->todo->priority) {
            'high'   => '🔴 Tinggi',
            'medium' => '🟡 Sedang',
            'low'    => '🟢 Rendah',
            default  => '-',
        };

        $message = "Halo *{$notifiable->name}*! 👋\n\n"
        . "📅 *Task Deadline Hari Ini!*\n\n"
        . "📌 *{$this->todo->title}*\n"
        . "⏰ Deadline: *{$deadline->format('H:i')}* hari ini\n\n"
        . "Jangan sampai telat ya! 💪\n\n"
        . "_— Claro App_";

        Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->post(config('services.fonnte.url'), [
            'target'  => $notifiable->phone,
            'message' => $message,
        ]);
    }
  
}
