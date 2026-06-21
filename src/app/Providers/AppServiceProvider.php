<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogUserActivity;
use Illuminate\Notifications\ChannelManager;
use App\Channels\WhatsappChannel;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class,  [LogUserActivity::class, 'handleLogin']);
        Event::listen(Logout::class, [LogUserActivity::class, 'handleLogout']);

      
    $this->app->make(\Illuminate\Notifications\ChannelManager::class)
        ->extend(\App\Channels\WhatsappChannel::class, function () {
            return new \App\Channels\WhatsappChannel();
        });
    }
}