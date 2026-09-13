<?php

namespace App\Console\Commands;

use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\Redact;
use App\Support\Alerts\Reports;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The quiet failures nothing else reports, checked every 5 minutes:
 *
 *  - the scheduler's own heartbeat — read back by [App\Http\Middleware\WatchScheduler] on web
 *    requests, because a dead cron cannot report itself;
 *  - the queue: production starts its worker from the deploy workflow with --max-time=3600 and
 *    nothing restarts it, so jobs (the SMS "payment matched" LINE push among them) quietly pile up
 *    until the next deploy. Jobs waiting 15+ minutes means nobody is working the queue;
 *  - queued jobs that failed for good — `failed_jobs` fills up with nobody reading it;
 *  - disk space — a full disk takes the whole site down, database included.
 *
 *   php artisan alerts:watchdog
 */
class AlertsWatchdogCommand extends Command
{
    protected $signature = 'alerts:watchdog';

    protected $description = 'Scheduler heartbeat + queue backlog + failed jobs + disk space (alerts the admin on Telegram).';

    public function handle(): int
    {
        Cache::forever('scheduler:heartbeat', now()->timestamp);

        if (! AdminAlerts::wants('system')) {
            return self::SUCCESS;
        }

        foreach (['checkQueue', 'checkFailedJobs', 'checkDisk'] as $check) {
            try {
                $this->{$check}();
            } catch (Throwable $e) {
                // One broken check must not silence the others.
                $this->warn("{$check}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function checkQueue(): void
    {
        [$waiting, $oldest] = Reports::queueBacklog(track: true);
        $this->line("queue: {$waiting} waiting, oldest " . ($oldest ?? '-') . ' min');
        if ($oldest === null || $oldest < 15) {
            AdminAlerts::forgetThrottle('queue-stuck');

            return;
        }

        AdminAlerts::send(new Alert(
            key: 'queue-stuck',
            level: $oldest >= 60 ? Alert::CRITICAL : Alert::WARNING,
            title: 'งานในคิวไม่ขยับมา ' . ($oldest >= 120 ? intdiv($oldest, 60) . ' ชม.' : $oldest . ' นาที'),
            body: 'queue worker น่าจะหยุดทำงาน — งานที่รออยู่ (เช่น แจ้ง LINE ตอนจับคู่ยอดเงิน, ส่งอีเมล) จะค้างจนกว่าจะมีคนสั่งรันใหม่'
                . "\nรันบนเซิร์ฟเวอร์: php artisan queue:work (หรือรัน workflow Queue Monitor)",
            facts: ['งานที่รอ' => number_format($waiting), 'ไม่ขยับมา' => $oldest . ' นาที', 'คิว' => (string) config('queue.default')],
            url: url('/admin'),
            urlLabel: 'เปิดหน้าแอดมิน',
            category: 'system',
        ), 120);
    }

    /** Jobs that used up their retries since the last look, grouped by job type. */
    private function checkFailedJobs(): void
    {
        $lastSeen = Cache::get('watchdog:failed_jobs:last_id');
        $maxId = (int) DB::table('failed_jobs')->max('id');
        Cache::forever('watchdog:failed_jobs:last_id', $maxId);
        if ($lastSeen === null || $maxId <= (int) $lastSeen) {
            return;     // first run starts the clock — old history is not news
        }

        $rows = DB::table('failed_jobs')->where('id', '>', (int) $lastSeen)->orderBy('id')->limit(200)->get(['payload', 'exception']);
        $byJob = [];
        $firstError = [];
        foreach ($rows as $row) {
            $name = class_basename((string) (json_decode((string) $row->payload, true)['displayName'] ?? 'งานไม่ทราบชื่อ'));
            $byJob[$name] = ($byJob[$name] ?? 0) + 1;
            $firstError[$name] ??= strtok((string) $row->exception, "\n") ?: '';
        }
        arsort($byJob);
        $top = array_key_first($byJob);
        $this->line('failed jobs: ' . count($rows));

        AdminAlerts::send(new Alert(
            key: 'failed-jobs',
            level: Alert::WARNING,
            title: 'งานเบื้องหลังล้มเหลว ' . number_format(count($rows)) . ' งาน',
            body: "ล้มเหลวหลังลองครบทุกครั้งแล้ว — ตัวอย่างสาเหตุ ({$top}):\n"
                . mb_substr(Redact::text($firstError[$top] ?? ''), 0, 300),
            facts: ['งานที่ล้มเหลว' => number_format(count($rows)) . ' งาน', 'ประเภท' => count($byJob) . ' แบบ'],
            bars: array_slice($byJob, 0, 6, true),
            url: url('/admin'),
            urlLabel: 'เปิดหน้าแอดมิน',
            category: 'system',
            barsLabel: 'แยกตามประเภทงาน',
        ), 180);
    }

    private function checkDisk(): void
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (! $free || ! $total) {
            return;
        }
        $gb = $free / 1024 ** 3;
        $pct = $free / $total * 100;
        $this->line(sprintf('disk: %.1f GB free (%.1f%%)', $gb, $pct));

        $level = match (true) {
            $gb < 2 || $pct < 3 => Alert::CRITICAL,
            $gb < 5 || $pct < 8 => Alert::WARNING,
            default => null,
        };
        if ($level === null) {
            AdminAlerts::forgetThrottle('disk-low:' . Alert::WARNING);
            AdminAlerts::forgetThrottle('disk-low:' . Alert::CRITICAL);

            return;
        }

        $usedPct = 100 - $pct;
        AdminAlerts::send(new Alert(
            key: 'disk-low:' . $level,
            level: $level,
            title: $level === Alert::CRITICAL ? 'ดิสก์เซิร์ฟเวอร์ใกล้เต็มมาก' : 'ดิสก์เซิร์ฟเวอร์เหลือน้อย',
            body: $level === Alert::CRITICAL
                ? 'ถ้าเต็ม เว็บจะเขียนไฟล์/ฐานข้อมูลไม่ได้และล่มทั้งเว็บ — ลบ log/วิดีโอ/ไฟล์ชั่วคราวที่ไม่ใช้ด่วน'
                : 'ควรเคลียร์ log เก่า วิดีโอที่สร้างแล้ว และไฟล์ชั่วคราว ก่อนดิสก์เต็ม',
            facts: [
                'เหลือว่าง' => number_format($gb, 1) . ' GB',
                'ใช้ไปแล้ว' => number_format($usedPct, 1) . '%',
                'ทั้งหมด' => number_format($total / 1024 ** 3, 0) . ' GB',
            ],
            bars: ['ใช้แล้ว' => (int) round($usedPct), 'ว่าง' => (int) round($pct)],
            category: 'system',
            barsLabel: 'สัดส่วนดิสก์ (%)',
        ), $level === Alert::CRITICAL ? 180 : 720);
    }
}
