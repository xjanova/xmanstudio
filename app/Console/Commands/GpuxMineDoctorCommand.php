<?php

namespace App\Console\Commands;

use App\Models\GpuJobEarning;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineEarningSettlementService;
use App\Services\GpuxMineHealthService;
use App\Services\GpuxMineRelayService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * ตรวจว่า GPUxMINE ใช้งานได้จริงตั้งแต่ต้นจนจบ — ก่อนเปิดใช้ และทุกครั้งที่สงสัย
 *
 * สิ่งที่พังในระบบนี้พังแบบเงียบ: ค่า env ขาดตัวเดียว เครื่องจับคู่ได้แต่ไม่เคยได้งาน, cron ตั้งเป็น
 * ทุกห้านาที ทุกอย่างช้าห้าเท่า, ตัวปล่อยเงินไม่รัน เงินค้าง pending ไปตลอด — โดยไม่มีหน้าไหนขึ้นแดง
 * คำสั่งนี้ถามทีละข้อแล้วบอกว่าต้องแก้ที่ไหน
 *
 * ไม่เขียนอะไรที่ relay หรือ aixman: relay ถูกถามรายชื่อเครื่อง ส่วน aixman ถูกยิงด้วย body ที่ไม่มี
 * workerId ซึ่ง aixman ปฏิเสธก่อนแตะฐานข้อมูลเสมอ ความลับไม่ถูกพิมพ์ออกมา
 *
 * จบด้วย FAILURE เมื่อมีข้อไหน "ไม่ผ่าน" (--strict: นับ "เตือน" ด้วย) — ใช้ในสคริปต์ deploy ได้
 */
class GpuxMineDoctorCommand extends Command
{
    protected $signature = 'gpuxmine:doctor {--strict : นับคำเตือนเป็นความล้มเหลวด้วย}';

    protected $description = 'ตรวจการตั้งค่า relay/aixman, ตัวจับเวลา และเงินที่ค้างของ GPUxMINE';

    private const OK = 'ok';

    private const WARN = 'warn';

    private const FAIL = 'fail';

    /** ตัวจับเวลาที่ต้องมี และจังหวะที่ต้องเป็น (routes/console.php) */
    private const SCHEDULE = [
        'gpuxmine:sync-nodes' => ['* * * * *', 'ทุกนาที'],
        'gpuxmine:settle-earnings' => ['0 * * * *', 'ทุกชั่วโมง'],
    ];

    /** @var array<int, array{0:string, 1:string, 2:string}> */
    private array $results = [];

    public function handle(
        GpuxMineRelayService $relay,
        GpuxMineDispatchService $dispatch,
        GpuxMineHealthService $health,
        GpuxMineEarningSettlementService $settlement,
        Schedule $schedule,
    ): int {
        $this->info('GPUxMINE doctor · ' . now()->timezone('Asia/Bangkok')->format('d/m/Y H:i') . ' น.');

        $this->checkConfig($relay, $dispatch, $settlement);
        $schemaReady = $this->checkSchema();
        $this->checkRelay($relay);
        $this->checkAixman($dispatch);
        $this->checkSchedule($schedule);

        if ($schemaReady) {
            $this->checkHeartbeats($health, $settlement);
            $this->checkMoney($health);
            $this->checkNodes($health, $relay);
        }

        return $this->report();
    }

    private function checkConfig(
        GpuxMineRelayService $relay,
        GpuxMineDispatchService $dispatch,
        GpuxMineEarningSettlementService $settlement,
    ): void {
        $relayUrl = (string) config('services.gpuxmine.relay_url');
        if (! $relay->isConfigured()) {
            $this->record(self::FAIL, 'ตั้งค่า relay', 'ขาด ' . $this->missing([
                'GPUXMINE_RELAY_URL' => $relayUrl,
                'GPUXMINE_RELAY_ADMIN_KEY' => (string) config('services.gpuxmine.admin_key'),
            ]) . ' — จับคู่เครื่องใหม่ไม่ได้ และไม่มีเครื่องไหนถูกส่งต่อ');
        } elseif ($problem = $this->urlProblem($relayUrl)) {
            $this->record(self::FAIL, 'ตั้งค่า relay', "GPUXMINE_RELAY_URL {$problem}");
        } else {
            $this->record(self::OK, 'ตั้งค่า relay', $relayUrl . ' · admin key ตั้งแล้ว');
        }

        $aixmanBase = (string) config('services.aixman.api_base');
        if (! $dispatch->isConfigured()) {
            $this->record(self::FAIL, 'ตั้งค่า aixman', 'ขาด ' . $this->missing([
                'AIXMAN_API_BASE' => $aixmanBase,
                'AIXMAN_WEBHOOK_SECRET' => (string) config('services.aixman.webhook_secret'),
            ]) . ' — เครื่องไม่ถูกส่งไปขึ้นทะเบียนรับงาน');
        } elseif ($problem = $this->urlProblem($aixmanBase)) {
            $this->record(self::FAIL, 'ตั้งค่า aixman', "AIXMAN_API_BASE {$problem}");
        } else {
            $this->record(self::OK, 'ตั้งค่า aixman', $aixmanBase . ' · webhook secret ตั้งแล้ว');
        }

        $hold = $settlement->holdHours();
        $cap = (int) config('services.gpuxmine.max_nodes_per_user', 10);
        $values = sprintf(
            'พักเงิน %d ชม. · เพดาน %s เครื่อง/บัญชี · ส่งซ้ำทุก %d นาที · ส่วนแบ่งผู้แนะนำที่ไม่มีผู้รับ: %s',
            $hold,
            $cap > 0 ? (string) $cap : 'ไม่จำกัด',
            max(1, (int) config('services.gpuxmine.resync_minutes', 10)),
            $settlement->unpaidReferralPolicy() === GpuJobEarning::REFERRAL_UNPAID_TO_PLATFORM
                ? 'แพลตฟอร์มเก็บ (GPUXMINE_UNPAID_REFERRAL=platform)'
                : 'คืนเจ้าของเครื่อง',
        );
        $this->record(
            $hold === 0 ? self::WARN : self::OK,
            'ค่าที่ใช้',
            $hold === 0 ? $values . ' — GPUXMINE_EARNING_HOLD_HOURS=0 คือไม่มีช่วงจับผลงานปลอมก่อนเงินออก' : $values,
        );
    }

    private function checkSchema(): bool
    {
        $required = [
            'gpu_nodes' => ['tunnel_token', 'dispatch_fingerprint', 'dispatch_worker_status', 'suspended_at', 'retire_status', 'banned_at'],
            'gpu_job_earnings' => ['prompt_id', 'donated_value_satang', 'referral_satang', 'cleared_at', 'void_reason', 'reviewed_by', 'referral_unpaid_satang'],
        ];

        $missing = [];
        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;

                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = "{$table}.{$column}";
                }
            }
        }

        if ($missing !== []) {
            $this->record(self::FAIL, 'ฐานข้อมูล', 'ไม่มี ' . implode(', ', $missing) . ' — รัน php artisan migrate');

            return false;
        }

        $this->record(self::OK, 'ฐานข้อมูล', 'migration ของ GPUxMINE ครบ');

        return true;
    }

    private function checkRelay(GpuxMineRelayService $relay): void
    {
        if (! $relay->isConfigured()) {
            return;   // บอกไปแล้วในหัวข้อตั้งค่า
        }

        $probe = $relay->probe();

        $probe['ok']
            ? $this->record(self::OK, 'relay', "ตอบแล้ว · รู้จัก {$probe['workers']} เครื่อง ออนไลน์ {$probe['online']}")
            : $this->record(self::FAIL, 'relay', (string) $probe['error']);
    }

    private function checkAixman(GpuxMineDispatchService $dispatch): void
    {
        if (! $dispatch->isConfigured()) {
            return;
        }

        $probe = $dispatch->probe();

        $probe['ok']
            ? $this->record(self::OK, 'aixman', 'ถึง /api/gpux/nodes และ aixman รับ webhook secret')
            : $this->record(self::FAIL, 'aixman', (string) $probe['error']);
    }

    private function checkSchedule(Schedule $schedule): void
    {
        $events = collect($schedule->events());

        foreach (self::SCHEDULE as $command => [$expression, $words]) {
            $event = $events->first(fn ($e) => str_contains((string) $e->command, $command));

            if ($event === null) {
                $this->record(self::FAIL, "ตัวตั้งเวลา {$command}", 'ไม่ได้ลงไว้ใน routes/console.php');
            } elseif ($event->expression !== $expression) {
                $this->record(self::FAIL, "ตัวตั้งเวลา {$command}", "ลงไว้เป็น {$event->expression} ต้องเป็น {$expression} ({$words})");
            } else {
                $this->record(self::OK, "ตัวตั้งเวลา {$command}", "{$expression} ({$words})");
            }
        }
    }

    /**
     * cron บนเซิร์ฟเวอร์เรียก schedule:run จริงไหม และถี่พอไหม
     *
     * ดูจากเวลาที่คำสั่งประทับไว้ตอนเริ่มรัน ไม่มีเวลาเลยในระบบที่มีเครื่องแล้ว = ไม่เคยรัน
     * (หรือเพิ่งล้างแคช — รอหนึ่งนาทีแล้วถามใหม่)
     */
    private function checkHeartbeats(GpuxMineHealthService $health, GpuxMineEarningSettlementService $settlement): void
    {
        $sync = $health->lastBeat(GpuxMineHealthService::TASK_SYNC);
        $lastPush = $health->lastPushAt();
        $pushNote = $lastPush ? ' · ส่งให้ aixman ล่าสุด ' . $this->ago($lastPush) : '';

        if ($sync === null) {
            $this->record(
                $health->pairedNodes() > 0 ? self::FAIL : self::WARN,
                'cron: sync-nodes',
                'ไม่มีบันทึกว่าเคยรัน — ตรวจ crontab ว่ามี * * * * * … php artisan schedule:run (ถ้าเพิ่งล้างแคช รอหนึ่งนาทีแล้วรันใหม่)' . $pushNote,
            );
        } else {
            $minutes = $sync->diffInMinutes(now(), true);
            $this->record(
                match (true) {
                    $minutes <= 3 => self::OK,
                    $minutes <= 10 => self::WARN,
                    default => self::FAIL,
                },
                'cron: sync-nodes',
                'รันล่าสุด ' . $this->ago($sync)
                    . ($minutes > 3 ? ' — ควรรันทุกนาที crontab อาจเป็น */5 หรือรอบก่อนค้าง' : '')
                    . $pushNote,
            );
        }

        $settle = $health->lastBeat(GpuxMineHealthService::TASK_SETTLE);
        if ($settle === null) {
            $this->record(self::WARN, 'cron: settle-earnings', 'ไม่มีบันทึกว่าเคยรัน — รันทุกชั่วโมง ถ้าเพิ่ง deploy หรือล้างแคชให้รอรอบถัดไป');
        } else {
            $minutes = $settle->diffInMinutes(now(), true);
            $this->record(
                match (true) {
                    $minutes <= 65 => self::OK,
                    $minutes <= 180 => self::WARN,
                    default => self::FAIL,
                },
                'cron: settle-earnings',
                'รันล่าสุด ' . $this->ago($settle) . ' · พักเงิน ' . $settlement->holdHours() . ' ชม.',
            );
        }
    }

    private function checkMoney(GpuxMineHealthService $health): void
    {
        $stuck = $health->stuckPending();
        $stuck['count'] > 0
            ? $this->record(self::FAIL, 'เงินค้างระยะพัก', sprintf(
                '%d งาน ฿%s พ้นระยะพักเกิน %d ชม. แล้วยังเป็น pending (เก่าสุด %s) — gpuxmine:settle-earnings ไม่ได้รันหรือล้ม',
                $stuck['count'],
                GpuxMineEarningSettlementService::baht($stuck['satang']),
                GpuxMineHealthService::STUCK_GRACE_HOURS,
                $this->ago($stuck['oldest']),
            ))
            : $this->record(self::OK, 'เงินค้างระยะพัก', 'ไม่มี');

        $cleared = $health->stuckCleared();
        $cleared['count'] > 0
            ? $this->record(self::WARN, 'เงินรอโอน', sprintf(
                '%d งาน ฿%s รอโอนเกิน %d ชม. — ปกติคือบัญชีหรือกระเป๋าที่ถูกปิด ถ้าไม่ใช่ ดู log [GPUxMINE] paying earnings',
                $cleared['count'],
                GpuxMineEarningSettlementService::baht($cleared['satang']),
                GpuxMineHealthService::STUCK_GRACE_HOURS,
            ))
            : $this->record(self::OK, 'เงินรอโอน', 'ไม่มีค้าง');

        $review = $health->awaitingReview();
        $review['count'] > 0
            ? $this->record(self::WARN, 'รอแอดมินตรวจ', sprintf(
                '%d งาน ฿%s — ตัดสินที่ %s',
                $review['count'],
                GpuxMineEarningSettlementService::baht($review['satang']),
                $this->adminUrl(),
            ))
            : $this->record(self::OK, 'รอแอดมินตรวจ', 'ไม่มี');
    }

    private function checkNodes(GpuxMineHealthService $health, GpuxMineRelayService $relay): void
    {
        $retirements = $health->staleRetirements();
        $retirements > 0
            ? $this->record(self::WARN, 'การถอนเครื่องที่ค้าง', "{$retirements} เครื่องถอนไม่สำเร็จมาเกิน " . GpuxMineHealthService::RETIRE_STALE_MINUTES . ' นาที — relay หรือ aixman ปฏิเสธ (ดู log GPUxMINE worker retirement)')
            : $this->record(self::OK, 'การถอนเครื่องที่ค้าง', 'ไม่มี');

        $awaiting = $health->awaitingRelayRetirements();
        if ($awaiting > 0) {
            $this->record(self::WARN, 'worker ค้างที่ relay', "{$awaiting} เครื่องถูกถอนที่ aixman แล้ว แต่ relay รุ่นนี้ยังไม่มีคำสั่งลบ worker — กุญแจของเครื่องยังต่อ relay ได้ (ไม่มีงานเข้า) อัปเกรด relay แล้ว gpuxmine:sync-nodes ลบให้เอง");
        }

        // กุญแจอุโมงค์ต้องใช้รายชื่อจาก relay — relay ตอบไม่ได้ก็บอกไปแล้วในหัวข้อ relay
        $live = $relay->isConfigured() ? $relay->workers() : null;
        if ($live !== null) {
            $tokens = $health->sharedTunnelTokens($live);
            if ($tokens['lost'] > 0) {
                $this->record(self::FAIL, 'กุญแจอุโมงค์', "{$tokens['lost']} เครื่องถูกออกกุญแจอุโมงค์ใหม่ที่ relay แต่ xmanstudio ไม่มีใบนั้น — aixman เรียกเครื่องเหล่านี้ไม่ได้เลย รัน php artisan gpuxmine:rotate-tunnel-tokens");
            } elseif ($tokens['legacy'] > 0) {
                $this->record(self::OK, 'กุญแจอุโมงค์', "{$tokens['legacy']} เครื่องยังใช้กุญแจใบเดียวทั้งเครื่องและ aixman — แยกได้ด้วย gpuxmine:rotate-tunnel-tokens หลังตั้ง Relay__IssueTunnelTokens=true");
            } else {
                $this->record(self::OK, 'กุญแจอุโมงค์', 'ทุกเครื่องมีกุญแจของ aixman แยกแล้ว');
            }
        }

        // ปลายทางในแต่ละแถว ไม่ใช่แค่ URL ใน config — ต้องได้ 0 ก่อน deploy aixman รุ่นที่ตรวจ https
        if ($relay->isConfigured()) {
            $endpoints = $health->staleTunnelEndpoints($relay);
            $examples = $endpoints['examples'] === [] ? '' : ' (เช่น ' . implode(', ', $endpoints['examples']) . ')';

            if ($endpoints['mismatched'] === 0) {
                $this->record(self::OK, 'ปลายทางอุโมงค์', 'ทุกเครื่องเก็บปลายทาง ' . rtrim((string) config('services.gpuxmine.relay_url'), '/') . '/w/… ตรงกับ GPUXMINE_RELAY_URL');
            } elseif ($endpoints['insecure'] > 0) {
                $this->record(self::FAIL, 'ปลายทางอุโมงค์', "{$endpoints['mismatched']} เครื่องเก็บปลายทางที่ไม่ตรงกับ GPUXMINE_RELAY_URL ในนั้น {$endpoints['insecure']} เครื่องไม่ใช่ https — aixman รุ่นใหม่ตอบ 400 ทุกครั้ง เครื่องไม่ได้งานเลย{$examples} gpuxmine:sync-nodes เขียนให้ตรงเองในรอบถัดไป ถ้าไม่หายดูว่า cron รันไหม");
            } else {
                $this->record(self::WARN, 'ปลายทางอุโมงค์', "{$endpoints['mismatched']} เครื่องเก็บปลายทางที่ไม่ตรงกับ GPUXMINE_RELAY_URL{$examples} — gpuxmine:sync-nodes เขียนให้ตรงเองเมื่อ relay ยังรู้จัก worker นั้น ที่ค้างอยู่คือ worker ที่ relay ไม่รู้จักแล้ว (เจ้าของต้องจับคู่ใหม่)");
            }
        }

        $errors = $health->dispatchErrors();
        $errors > 0
            ? $this->record(self::WARN, 'ส่งให้ aixman ไม่สำเร็จ', "{$errors} เครื่อง — ระบบลองซ้ำทุกรอบเอง ถ้าไม่หาย ดู dispatch_note ที่หน้าแอดมิน")
            : $this->record(self::OK, 'ส่งให้ aixman ไม่สำเร็จ', 'ไม่มี');
    }

    private function report(): int
    {
        $counts = [self::OK => 0, self::WARN => 0, self::FAIL => 0];

        $this->newLine();
        foreach ($this->results as [$level, $check, $detail]) {
            $counts[$level]++;
            $tag = match ($level) {
                self::OK => '<fg=green>  ผ่าน </>',
                self::WARN => '<fg=yellow>  เตือน</>',
                default => '<fg=red>ไม่ผ่าน</>',
            };
            $this->line("{$tag}  <options=bold>{$check}</>  {$detail}");
        }

        $this->newLine();
        $this->line(sprintf('ผ่าน %d · เตือน %d · ไม่ผ่าน %d', $counts[self::OK], $counts[self::WARN], $counts[self::FAIL]));

        $failed = $counts[self::FAIL] > 0 || ($this->option('strict') && $counts[self::WARN] > 0);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function record(string $level, string $check, string $detail): void
    {
        $this->results[] = [$level, $check, $detail];
    }

    /** @param  array<string, string>  $values  ชื่อ env => ค่า (ใช้ดูว่าว่างไหมเท่านั้น ไม่พิมพ์ค่า) */
    private function missing(array $values): string
    {
        return implode(', ', array_keys(array_filter($values, fn (string $value) => trim($value) === '')));
    }

    /**
     * ปลายทางที่ส่งกุญแจของเครื่องไปด้วยต้องเป็น https — http ได้เฉพาะเครื่องตัวเองตอนพัฒนา
     */
    private function urlProblem(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            return 'ไม่ใช่ URL ที่ใช้ได้';
        }

        if ($scheme === 'http' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return 'เป็น http — กุญแจของเครื่องจะวิ่งผ่านเน็ตแบบไม่เข้ารหัส ต้องเป็น https';
        }

        return null;
    }

    private function ago(?Carbon $at): string
    {
        return $at === null
            ? '—'
            : $at->copy()->timezone('Asia/Bangkok')->format('d/m H:i') . ' น. (' . $at->diffForHumans() . ')';
    }

    private function adminUrl(): string
    {
        try {
            return route('admin.gpuxmine.earnings', ['status' => 'review']);
        } catch (\Throwable) {
            return '/admin/gpuxmine/earnings';
        }
    }
}
