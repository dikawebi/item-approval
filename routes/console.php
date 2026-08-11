<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('d365:sync-item-groups')->daily();
Schedule::command('d365:sync-item-model-groups')->daily();
Schedule::command('item-requests:aging-digest')->dailyAt('08:00');
