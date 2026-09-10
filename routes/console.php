<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks — PRD FR-10, FR-11, FR-16
|--------------------------------------------------------------------------
|
| Automated daily early warning expiration checks for contract monitoring.
|
*/
Schedule::command('contracts:check-expiration')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/expiration-check.log'));

