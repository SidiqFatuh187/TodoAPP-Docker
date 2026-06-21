<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class WhatsappChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toWhatsapp')) {
            $notification->toWhatsapp($notifiable);
        }
    }
}