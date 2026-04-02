<?php

use App\Jobs\RetryFailedWebhooks;
use App\Jobs\CheckAlertRules;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RetryFailedWebhooks())->everyMinute();
Schedule::job(new CheckAlertRules())->everyFiveMinutes();
