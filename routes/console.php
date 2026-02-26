<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Project;
use App\Jobs\SyncProjectDataJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the Master Data Sync to run daily at 01:00 AM (off-peak hours)
Schedule::call(function () {
    // Dispatch a sync job for every active project in the database
    Project::chunk(100, function ($projects) {
        foreach ($projects as $project) {
            SyncProjectDataJob::dispatch($project);
        }
    });
})->dailyAt('01:00');

// Automatically process pending or failed Asaas webhooks every minute
Schedule::command('asaas:process-events')->everyMinute();
