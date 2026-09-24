<?php

use App\Jobs\AutoGenerateVideoJob;
use App\Jobs\GenerateAndPostPromoCommentJob;
use App\Jobs\RunAutomationScheduleJob;
use App\Jobs\UploadVideoJob;
use App\Models\MetalXAutomationLog;
use App\Models\MetalXPromoComment;
use App\Models\MetalXVideoProject;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        Log::info('[SMS Checker] Cleanup completed successfully');
    })
    ->onFailure(function () {
        Log::error('[SMS Checker] Cleanup failed');
    });

// Admin Telegram alerts (/admin/alerts). All of these self-gate when Telegram is not set up, so they
// are always safe to schedule. Overlap locks expire in minutes, not the default 24 hours: a run
// killed by a deploy or a reboot must not silence the watchdog for a day.
// Every minute: the scheduler's heartbeat, written by the scheduler itself — page views read it
// back (WatchScheduler), because a dead cron cannot report itself.
Schedule::call(fn () => Cache::forever('scheduler:heartbeat', now()->timestamp))
    ->name('alerts-heartbeat')
    ->everyMinute();

// Every 5 min: a stuck queue, jobs that failed for good, disk space.
Schedule::command('alerts:watchdog')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();

// Hourly: new members as one card (not one ping per signup), plus anything the per-category hourly
// ceilings folded away.
Schedule::command('alerts:digest')
    ->hourly()
    ->withoutOverlapping(30)
    ->runInBackground();

// 09:00 Thai time: yesterday on one card — sales with a 7-day chart, best sellers, what is waiting.
Schedule::command('alerts:daily-report')
    ->dailyAt('09:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground();

// Product Releases: ดึง release ล่าสุดจาก GitHub เข้า product_versions
// 2026-07-28 — เดิมต้องกดปุ่ม Sync ในหน้า admin เอง ถ้าลืมกด API เช็คอัพเดทจะ
// โฆษณาเวอร์ชันเก่าค้างไว้ แอปลูกค้าเลยตอบ "ไม่มีอัพเดท" ทั้งที่ GitHub มีของใหม่แล้ว
// รันทุก 10 นาที แต่แอปที่ไม่มี token (โควตาร่วม 60 ครั้ง/ชม. ของเซิร์ฟเวอร์) ถูกถามอย่างมากทุก 30 นาที
// และข้ามทั้งรอบเมื่อ GitHub บอกว่าโควตาหมด — ดู GithubReleaseService::TOKENLESS_SYNC_MINUTES
Schedule::command('products:sync-releases')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Product Releases] Sync failed');
    });

// Bug Reports: ลบ Bug Reports เก่าอัตโนมัติตามจำนวนวันที่ตั้งค่าไว้
// รันทุกวันตอนตี 2
Schedule::command('bugreports:cleanup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('[Bug Reports] Cleanup completed successfully');
    })
    ->onFailure(function () {
        Log::error('[Bug Reports] Cleanup failed');
    });

// Metal-X: ผลิตวิดีโอจาก Content Plans อัตโนมัติ
// รันทุก 5 นาที: ตรวจสอบ content plans ที่ถึงเวลาและสร้างโปรเจกต์ใหม่
Schedule::job(new AutoGenerateVideoJob)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('[Metal-X Auto Generate] Completed');
    })
    ->onFailure(function () {
        Log::error('[Metal-X Auto Generate] Failed');
    });

// Metal-X: ระบบอัตโนมัติจัดการ YouTube (ตอบคอมเม้นต์, ไลค์, โปรโมท, ตรวจสอบ)
// รันทุก 5 นาที: ตรวจสอบ schedules ที่ถึงเวลาและ dispatch jobs
Schedule::job(new RunAutomationScheduleJob)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('[Metal-X Automation] Schedule run completed');
    })
    ->onFailure(function () {
        Log::error('[Metal-X Automation] Schedule run failed');
    });

// Metal-X: โพส Promo Comments ที่ถึงเวลา
// รันทุก 15 นาที: ตรวจหา promo comments ที่ status=scheduled และ scheduled_at <= now
Schedule::call(function () {
    $promos = MetalXPromoComment::readyToPost()->get();
    foreach ($promos as $promo) {
        GenerateAndPostPromoCommentJob::dispatch($promo->video, false);
    }
})
    ->name('metalx-post-promo-comments')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Metal-X: ล้าง Automation Logs เก่ากว่า 30 วัน
// รันทุกวันตอนตี 3
Schedule::call(function () {
    $days = config('metalx.automation.log_retention_days', 30);
    $deleted = MetalXAutomationLog::where('created_at', '<', now()->subDays($days))->delete();
    Log::info("[Metal-X Automation] Cleaned up {$deleted} old log entries (retention: {$days} days)");
})
    ->name('metalx-cleanup-logs')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Torrent: Mark stale seeders as offline
// Run every 2 minutes: set is_online=false for seeders not seen in 5 minutes
Schedule::command('torrent:cleanup-seeders')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('[Torrent] Stale seeders cleanup completed successfully');
    })
    ->onFailure(function () {
        Log::error('[Torrent] Stale seeders cleanup failed');
    });

// VPN Proxy: Health-check VPN Gate servers, cache only reachable ones
// Run every 30 minutes: TCP-test each server, store healthy list for API
Schedule::command('vpn:health-check')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('[VPN Health Check] Completed successfully');
    })
    ->onFailure(function () {
        Log::error('[VPN Health Check] Failed');
    });

// WireGuard: Health-check WireGuard servers and update peer status
Schedule::command('wireguard:health-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('[WireGuard Health Check] Completed successfully');
    })
    ->onFailure(function () {
        Log::error('[WireGuard Health Check] Failed');
    });

// Metal-X: อัปโหลดวิดีโอ Projects ที่ถึงเวลา
// รันทุก 5 นาที: ตรวจหา projects ที่ status=rendered และ scheduled_at <= now
Schedule::call(function () {
    $projects = MetalXVideoProject::readyToPublish()->get();
    foreach ($projects as $project) {
        UploadVideoJob::dispatch($project);
    }
})
    ->name('metalx-publish-scheduled-videos')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// ใบเสนอราคา: เตือนลูกค้าหนึ่งครั้งก่อนหมดอายุ
// วันละครั้งตอนเก้าโมง — เตือนตอนตีสามไม่มีใครอ่าน และเห็นอีเมลตอนเช้าดูเหมือน
// คนตามงาน ไม่ใช่สคริปต์ withoutOverlapping กันส่งซ้ำถ้ารันทับกัน
Schedule::command('quotations:follow-up')
    ->dailyAt('09:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Quotation] follow-up reminders failed');
    });

// GPUxMINE: ดึงสถานะเครื่องจาก relay แล้วขึ้นทะเบียนรับงานที่ aixman
// ทุกนาที เพราะเครื่องที่บ้านคนเปิด-ปิดตามใจเจ้าของ และเครื่องที่ประเมินตัวเอง
// เสร็จตอนตีสามต้องได้งานตอนตีสาม ไม่ใช่ตอนเจ้าของตื่นมาเปิดเว็บ
// (cron บนเซิร์ฟเวอร์ต้องเป็น * * * * * — ถ้าตั้งไว้ทุกห้านาที ทุกอย่างช้าตามห้าเท่า)
// ล็อกกันรันซ้อนหมดอายุในห้านาที: ค่าเริ่มต้นคือ 24 ชั่วโมง รอบที่ถูกฆ่ากลางทาง
// จะทิ้งล็อกไว้จนไม่มีเครื่องไหนถูกส่งต่อได้ทั้งวัน
Schedule::command('gpuxmine:sync-nodes')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[GPUxMINE] node sync failed');
    });

// GPUxMINE: รายได้จากงานพักไว้ตาม GPUXMINE_EARNING_HOLD_HOURS แล้วโอนเข้ากระเป๋า
// ทุกชั่วโมง — เจ้าของเครื่องไม่ต้องรอใครกดจ่าย ส่วนงานที่ติด "รอตรวจสอบ" รอแอดมิน
// ล็อกกันรันซ้อนหมดอายุใน 55 นาที: รอบที่ถูกฆ่ากลางทางต้องไม่ขวางการจ่ายทั้งวัน
// (ค่าเริ่มต้น 24 ชั่วโมง) และรอบที่ทับกันจริงก็ยังจ่ายซ้ำไม่ได้ — ดู
// GpuxMineEarningSettlementService
Schedule::command('gpuxmine:settle-earnings')
    ->hourly()
    ->withoutOverlapping(55)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[GPUxMINE] settling earnings failed');
    });

// โดเมน: ตามเก็บออเดอร์ที่ค้าง — ลูกค้าจ่ายเงินแล้วแต่ยังไม่ได้โดเมน
// ทุกห้านาที เพราะคนที่เพิ่งจ่ายเงินไปนั่งรออยู่หน้าจอ ถ้าปล่อยถึงชั่วโมงละครั้ง
// คนที่เจอเน็ตกระตุกตอนกดซื้อจะเห็นแค่ "กำลังดำเนินการ" โดยไม่มีใครมาสะสาง
// ตัวคำสั่งเองมี backoff ต่อแถวและจำกัด 30 แถวต่อรอบ จึงไม่กิน quota ของ API
Schedule::command('domains:reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Domains] reconcile failed');
    });

// โดเมน: แจ้งเตือนล่วงหน้าแล้วต่ออายุอัตโนมัติจากกระเป๋าเงิน
// วันละครั้ง — หน้าโดเมนสัญญากับลูกค้าไว้ว่า "ตัดจากกระเป๋าเงินก่อนหมดอายุ 30 วัน
// เราแจ้งล่วงหน้าทุกครั้ง" คำสั่งนี้คือสิ่งที่ทำให้ประโยคนั้นเป็นจริง
// เงินไม่พอไม่ใช่ความล้มเหลว — ข้ามแล้วลองใหม่พรุ่งนี้ ยังเหลือเวลาอีกเป็นเดือน
Schedule::command('domains:renew')
    ->dailyAt('09:30')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Domains] renewal run failed');
    });

// โดเมน: ดึงราคาต้นทุนจริงจากผู้ให้บริการมาอัปเดตแคตตาล็อก
// วันละครั้งตอนตีสี่ — ผู้ให้บริการขึ้นราคาเมื่อไหร่ก็ได้ และแคตตาล็อกที่ค้าง
// อยู่หลายเดือนคือแคตตาล็อกที่ขายบางนามสกุลต่ำกว่าทุน
Schedule::command('domains:sync-catalogue')
    ->dailyAt('04:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Domains] catalogue sync failed');
    });

// โดเมน: ตามสถานะจริงจากผู้ให้บริการวันละครั้ง — ตีห้า
// โดเมนที่หมดอายุแล้วต้องขึ้นว่าหมดอายุ (เดิมค้าง "ใช้งานอยู่" ตลอดไป) และโดเมนที่
// ไม่รู้ว่าผูกกับ subscription ไหนจะต่ออายุจากกระเป๋าเงินไม่ได้ ต้องบอกแอดมินก่อนถึงวัน
Schedule::command('domains:sync-status')
    ->dailyAt('05:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Domains] status sync failed');
    });

// ผู้ให้บริการ: บัตรในบัญชี Hostinger ที่ใช้ซื้อโดเมน/VPS ให้ลูกค้า ยังจ่ายได้ไหม
// ทุก 6 ชั่วโมง — บัตรหมดอายุ/ถูกระงับ/ไม่มีบัตรหลัก = แจ้ง Telegram และหยุดรับคำสั่งซื้อ
// ก่อนลูกค้าคนแรกจะโดนปฏิเสธ · และปิด auto-renew ฝั่งผู้ให้บริการที่หลุดเปิดไว้
// (ของที่เราขายต่อต้องต่ออายุจากกระเป๋าลูกค้าเท่านั้น ไม่งั้นบัตรเราโดนตัดเงิน)
Schedule::command('hostinger:billing-check --quiet-ok')
    ->cron('15 */6 * * *')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Billing] supplier billing check failed');
    });

// VPS: ติดตั้งเครื่องที่ซื้อค้าง (202) หาเครื่องที่ซื้อแล้วแต่คำตอบหาย และคืนเงินออเดอร์
// ที่ไม่เคยถึงผู้ให้บริการ — ทุกห้านาทีเพราะลูกค้านั่งรอหน้าจออยู่
Schedule::command('vps:reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[VPS] reconcile failed');
    });

// VPS: แจ้งล่วงหน้า ตัดเงินต่ออายุจากกระเป๋า เตือนเครื่องที่ไม่ต่อ และตั้งสถานะหมดอายุ
Schedule::command('vps:renew')
    ->dailyAt('09:40')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[VPS] renewal run failed');
    });

// VPS: ดึงแพ็กเกจและราคาต้นทุนจริงจากผู้ให้บริการ — ตีสี่สิบนาที หลังแคตตาล็อกโดเมน
Schedule::command('vps:sync-catalogue')
    ->dailyAt('04:10')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[VPS] catalogue sync failed');
    });

// ความปลอดภัย: ลบประวัติการเข้าสู่ระบบที่เก่าเกินกำหนด และเก็บกวาดบล็อกที่หมดอายุ
// ตีสามทุกวัน — ประวัติการล็อกอินคือข้อมูลส่วนบุคคล เก็บไว้เท่าที่ต้องใช้สอบสวน
// ไม่ใช่เก็บตลอดไป และรายการบล็อกที่ไม่เคยถูกเก็บกวาดจะอ่านไม่รู้เรื่องภายในสัปดาห์เดียว
Schedule::command('security:prune')
    ->dailyAt('03:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('[Security] login log prune failed');
    });
