<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// SMS Checker: ยกเลิก Orders หมดเวลาและทำความสะอาดข้อมูล
// รันทุก 5 นาที: ยกเลิก orders ที่หมดเวลาชำระ (30 นาที)
Schedule::command('smschecker:cleanup')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[SMS Checker] Cleanup completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[SMS Checker] Cleanup failed');
    });
