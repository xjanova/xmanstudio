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

// Bug Reports: ลบ Bug Reports เก่าอัตโนมัติตามจำนวนวันที่ตั้งค่าไว้
// รันทุกวันตอนตี 2
Schedule::command('bugreports:cleanup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[Bug Reports] Cleanup completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Bug Reports] Cleanup failed');
    });

// Metal-X: ระบบอัตโนมัติจัดการ YouTube (ตอบคอมเม้นต์, ไลค์, โปรโมท, ตรวจสอบ)
// รันทุก 5 นาที: ตรวจสอบ schedules ที่ถึงเวลาและ dispatch jobs
Schedule::job(new \App\Jobs\RunAutomationScheduleJob)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[Metal-X Automation] Schedule run completed');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Metal-X Automation] Schedule run failed');
    });

// Metal-X: โพส Promo Comments ที่ถึงเวลา
// รันทุก 15 นาที: ตรวจหา promo comments ที่ status=scheduled และ scheduled_at <= now
Schedule::call(function () {
    $promos = \App\Models\MetalXPromoComment::readyToPost()->get();
    foreach ($promos as $promo) {
        \App\Jobs\GenerateAndPostPromoCommentJob::dispatch($promo->video, false);
    }
})
    ->name('metalx-post-promo-comments')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Metal-X: ล้าง Automation Logs เก่ากว่า 30 วัน
// รันทุกวันตอนตี 3
Schedule::call(function () {
    $days = config('metalx.automation.log_retention_days', 30);
    $deleted = \App\Models\MetalXAutomationLog::where('created_at', '<', now()->subDays($days))->delete();
    \Illuminate\Support\Facades\Log::info("[Metal-X Automation] Cleaned up {$deleted} old log entries (retention: {$days} days)");
})
    ->name('metalx-cleanup-logs')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Metal-X: อัปโหลดวิดีโอ Projects ที่ถึงเวลา
// รันทุก 5 นาที: ตรวจหา projects ที่ status=rendered และ scheduled_at <= now
Schedule::call(function () {
    $projects = \App\Models\MetalXVideoProject::readyToPublish()->get();
    foreach ($projects as $project) {
        \App\Jobs\UploadVideoJob::dispatch($project);
    }
})
    ->name('metalx-publish-scheduled-videos')
    ->everyFiveMinutes()
    ->withoutOverlapping();
