<?php

use App\Models\SupportClick;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:import-geoip')->monthlyOn(7, '03:30')->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [SupportClick::class]])->dailyAt('03:00')->withoutOverlapping();
Schedule::command('posts:purge-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping();
