<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notifiable->is_banned && $notifiable->phone && method_exists($notification, 'toWhatsapp')) {
            $this->sendMessage($notifiable->phone, $notification->toWhatsapp($notifiable));
        }
    }

    public function sendMessage(string $phone, string $message): void
    {
        $response = Http::withHeaders(['Authorization' => config('services.fonnte.token')])
            ->connectTimeout(5)
            ->timeout(15)
            ->post(config('services.fonnte.url'), ['target' => $phone, 'message' => $message]);

        $response->throw();
        if ($response->json('status') !== true) {
            throw new RuntimeException('Fonnte did not accept the WhatsApp message.');
        }
    }
}