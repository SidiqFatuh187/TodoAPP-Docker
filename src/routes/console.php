<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-task-notifications --timezone=Asia/Jakarta')->dailyAt('07:00');
Schedule::command('app:send-task-notifications --timezone=Asia/Makassar')->dailyAt('06:00'); // 07.00 WITA = 06.00 WIB
Schedule::command('app:send-task-notifications --timezone=Asia/Jayapura')->dailyAt('05:00'); // 07.00 WIT = 05.00 WIB