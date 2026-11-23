<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:clean --disable-notifications')
    ->dailyAt('01:00')
    ->withoutOverlapping(30);

Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt('01:30')
    ->withoutOverlapping(30);
