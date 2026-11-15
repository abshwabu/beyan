<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:sqlite --keep-days=14')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

Schedule::command('platform:subscriptions:check-expiry')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping();
