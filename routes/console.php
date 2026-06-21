<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Yearly runs after monthly so a same-day catch-up can cascade in one cycle
// if the server was down: monthly fills gaps first, yearly compacts them.
Schedule::command('glucose:rollup-monthly')->dailyAt('02:00');
Schedule::command('glucose:rollup-yearly')->dailyAt('02:30');
