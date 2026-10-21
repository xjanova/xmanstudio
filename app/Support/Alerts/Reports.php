<?php

namespace App\Support\Alerts;

use App\Models\Order;
use App\Models\Quotation;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WalletTopup;
use App\Support\Telegram\TelegramBot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * The report cards: the 09:00 daily summary, and what the bot answers to /today, /week, /month,
 * /pending, /status and /security.
 *
 * Only MEASURED numbers — a report the owner cannot trust is worse than none. Sales are orders that
 * are actually paid (by paid_at, falling back to created_at for checkouts that create an order
 * already paid) plus completed rental payments. Wallet top-ups are not sales: the money is counted
 * when the wallet is spent on an order.
 */
final class Reports
{
    private const TZ = 'Asia/Bangkok';

    private const MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private const DAYS = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

    // ================================================================================ periods

    /** One Thai calendar day: yesterday for the 09:00 report, today for /today. */
    public static function day(CarbonImmutable $day, bool $soFar = false): Alert
    {
        $day = $day->setTimezone(self::TZ)->startOfDay();
        [$from, $to] = [$day->utc(), $day->addDay()->utc()];

        $sales = self::sales($from, $to);
        $prev = self::sales($day->subDay()->utc(), $from);
        $members = User::whereBetween('created_at', [$from, $to])->count();
        $contacts = SupportTicket::whereBetween('created_at', [$from, $to])->count()
            + Quotation::whereBetween('created_at', [$from, $to])->count();

        // The week leading up to the day, the day itself last and highlighted.
        $columns = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $day->subDays($i);
            $columns[self::DAYS[$d->dayOfWeek] . ' ' . $d->day] = self::sales($d->utc(), $d->addDay()->utc())['amount'];
        }

        $pending = self::pendingCounts();
        $lines = [];
        $lines[] = $sales['count'] > 0
            ? '• ชำระแล้ว ' . $sales['count'] . ' รายการ' . ($sales['rentals'] > 0 ? ' (ค่าเช่า ' . $sales['rentals'] . ')' : '')
            : '• ยังไม่มีรายการที่ชำระเงิน' . ($soFar ? 'วันนี้' : 'ในวันนั้น');
        foreach (self::pendingLines($pending) as $line) {
            $lines[] = $line;
        }

        $title = ($soFar ? 'ยอดวันนี้ ' : 'สรุปประจำวัน ') . self::thaiDate($day) . ($soFar ? ' (ถึง ' . CarbonImmutable::now(self::TZ)->format('H:i') . ' น.)' : '');

        return new Alert(
            key: ($soFar ? 'report-today:' : 'daily-report:') . $day->toDateString(),
            level: array_sum($pending) > 0 ? Alert::WARNING : Alert::OK,
            title: $title,
            body: implode("\n", $lines),
            facts: [
                'ยอดขาย' => BusinessAlerts::baht($sales['amount']) . self::trend($sales['amount'], $prev['amount']),
                'รายการ' => number_format($sales['count']),
                'สมาชิกใหม่' => number_format($members) . ' คน',
                'ติดต่อ/ขอราคา' => number_format($contacts),
            ],
            bars: self::topProducts($from, $to),
            url: self::url('admin.dashboard'),
            urlLabel: 'เปิดแดชบอร์ด',
            category: 'daily',
            barsLabel: 'สินค้าขายดี (บาท)',
            columns: $columns,
            columnsLabel: 'ยอดขาย 7 วัน (บาท)',
        );
    }

    /** The last 7 days, a column per day. */
    public static function week(): Alert
    {
        $today = CarbonImmutable::now(self::TZ)->startOfDay();
        $from = $today->subDays(6);
        $columns = [];
        for ($d = $from; $d <= $today; $d = $d->addDay()) {
            $columns[self::DAYS[$d->dayOfWeek] . ' ' . $d->day] = self::sales($d->utc(), $d->addDay()->utc())['amount'];
        }
        $now = self::sales($from->utc(), $today->addDay()->utc());
        $before = self::sales($from->subDays(7)->utc(), $from->utc());
        $best = array_keys($columns, max($columns))[0] ?? '—';

        return new Alert(
            key: 'report-week:' . $today->toDateString(),
            level: Alert::OK,
            title: 'ยอดขาย 7 วันล่าสุด ' . BusinessAlerts::baht($now['amount']),
            body: max($columns) > 0 ? 'วันที่ขายดีที่สุด: ' . $best : 'ยังไม่มียอดขายในสัปดาห์นี้',
            facts: [
                'รวม 7 วัน' => BusinessAlerts::baht($now['amount']) . self::trend($now['amount'], $before['amount']),
                'รายการ' => number_format($now['count']),
                'เฉลี่ย/วัน' => BusinessAlerts::baht(round($now['amount'] / 7)),
                'เฉลี่ย/บิล' => $now['count'] > 0 ? BusinessAlerts::baht(round($now['amount'] / $now['count'])) : '—',
            ],
            bars: self::topProducts($from->utc(), $today->addDay()->utc()),
            url: self::url('admin.dashboard'),
            urlLabel: 'เปิดแดชบอร์ด',
            category: 'daily',
            barsLabel: 'สินค้าขายดี 7 วัน (บาท)',
            columns: $columns,
            columnsLabel: 'ยอดขายรายวัน (บาท)',
        );
    }

    /** The last 6 months, a column per month. */
    public static function months(): Alert
    {
        $thisMonth = CarbonImmutable::now(self::TZ)->startOfMonth();
        $columns = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $thisMonth->subMonths($i);
            $columns[self::MONTHS[$m->month - 1]] = self::sales($m->utc(), $m->addMonth()->utc())['amount'];
        }
        $now = self::sales($thisMonth->utc(), $thisMonth->addMonth()->utc());
        $last = self::sales($thisMonth->subMonth()->utc(), $thisMonth->utc());

        return new Alert(
            key: 'report-months:' . $thisMonth->format('Y-m'),
            level: Alert::OK,
            title: 'ยอดขายเดือน' . self::MONTHS[$thisMonth->month - 1] . ' ' . BusinessAlerts::baht($now['amount']),
            body: 'เดือนก่อน: ' . BusinessAlerts::baht($last['amount']) . ' · ย้อนหลัง 6 เดือนรวม ' . BusinessAlerts::baht(array_sum($columns)),
            facts: [
                'เดือนนี้' => BusinessAlerts::baht($now['amount']) . self::trend($now['amount'], $last['amount']),
                'รายการ' => number_format($now['count']),
                'สมาชิกใหม่' => number_format(User::where('created_at', '>=', $thisMonth->utc())->count()) . ' คน',
            ],
            bars: self::topProducts($thisMonth->utc(), $thisMonth->addMonth()->utc()),
            url: self::url('admin.dashboard'),
            urlLabel: 'เปิดแดชบอร์ด',
            category: 'daily',
            barsLabel: 'สินค้าขายดีเดือนนี้ (บาท)',
            columns: $columns,
            columnsLabel: 'ยอดขายรายเดือน (บาท)',
        );
    }

    // ================================================================================ work queue

    /**
     * Orders with a slip waiting to be checked, newest first. Filtered in PHP: product checkouts keep
     * the slip inside metadata that is stored as double-encoded JSON text, which no JSON-path query
     * (on MySQL or SQLite) can see into.
     */
    private static function slipOrders(): Collection
    {
        return Order::whereIn('payment_status', ['pending', 'verifying', 'processing'])
            ->where('created_at', '>=', now()->subDays(14))
            ->latest('id')->limit(300)->get()
            ->filter(fn (Order $o) => $o->payment_status !== 'pending' || BusinessAlerts::orderSlip($o) !== null)
            ->values();
    }

    /** Pending top-ups a person has to approve: TrueMoney, or a transfer the SMS matcher parked for review. */
    private static function manualTopups(): Builder
    {
        return WalletTopup::where('status', WalletTopup::STATUS_PENDING)
            ->where(fn (Builder $q) => $q->where('payment_method', WalletTopup::METHOD_TRUEMONEY)
                ->orWhere('sms_verification_status', 'matched'));
    }

    /** @return array<string,int> what is waiting on a person, by kind */
    public static function pendingCounts(): array
    {
        return [
            'slips' => self::slipOrders()->count(),
            'topups' => self::manualTopups()->count(),
            'rentals' => RentalPayment::where('status', RentalPayment::STATUS_PROCESSING)->count(),
            'tickets' => SupportTicket::whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_WAITING_REPLY])->count(),
            'quotes' => Quotation::whereIn('status', ['draft', 'sent'])->where('created_at', '>=', now()->subDays(14))->count(),
        ];
    }

    /** @param array<string,int> $counts */
    private static function pendingLines(array $counts): array
    {
        $labels = [
            'slips' => 'ออเดอร์รอตรวจ/ยืนยัน',
            'topups' => 'เติมเงินรอตรวจ',
            'rentals' => 'ค่าเช่ารอตรวจ',
            'tickets' => 'ตั๋วซัพพอร์ตรอตอบ',
            'quotes' => 'ใบเสนอราคาใหม่ (14 วัน)',
        ];
        $lines = [];
        foreach ($counts as $k => $n) {
            if ($n > 0) {
                $lines[] = '• รอดำเนินการ: ' . $labels[$k] . ' ' . $n;
            }
        }

        return $lines;
    }

    /** The /pending card, plus the items themselves for the buttons under it. */
    public static function pending(): Alert
    {
        $counts = self::pendingCounts();
        $labels = ['slips' => 'ออเดอร์รอตรวจ/ยืนยัน', 'topups' => 'เติมเงิน', 'rentals' => 'ค่าเช่า', 'tickets' => 'ตั๋วซัพพอร์ต', 'quotes' => 'ใบเสนอราคา'];
        $bars = [];
        foreach ($counts as $k => $n) {
            if ($n > 0) {
                $bars[$labels[$k]] = $n;
            }
        }
        $total = array_sum($counts);

        return new Alert(
            key: 'report-pending:' . now()->timestamp,
            level: $total > 0 ? Alert::WARNING : Alert::OK,
            title: $total > 0 ? 'งานที่รอคุณอยู่ ' . number_format($total) . ' เรื่อง' : 'ไม่มีงานค้าง เคลียร์หมดแล้ว',
            body: $total > 0 ? 'กดรายการด้านล่างเพื่อเปิดการ์ดพร้อมปุ่มยืนยัน/ปฏิเสธ' : 'ไม่มีสลิป เติมเงิน ค่าเช่า หรือตั๋วที่รอดำเนินการ',
            facts: [
                'ออเดอร์รอยืนยัน' => number_format($counts['slips']),
                'ตั๋วรอตอบ' => number_format($counts['tickets']),
                'อื่นๆ' => number_format($counts['topups'] + $counts['rentals'] + $counts['quotes']),
            ],
            bars: $bars,
            category: 'daily',
            barsLabel: 'แยกตามประเภท',
        );
    }

    /**
     * The items behind /pending, newest first: [label, action kind, id].
     *
     * @return array<int,array{0:string,1:string,2:int}>
     */
    public static function pendingItems(int $limit = 8): array
    {
        $items = [];
        foreach (self::slipOrders()->take($limit) as $o) {
            $items[] = ['🧾 ' . $o->order_number . ' · ' . BusinessAlerts::baht((float) ($o->payment_display_amount ?: $o->total)), 'o', (int) $o->id];
        }
        foreach (self::manualTopups()->latest('id')->limit($limit)->get() as $t) {
            $items[] = ['👛 ' . $t->topup_id . ' · ' . BusinessAlerts::baht((float) $t->amount), 't', (int) $t->id];
        }
        foreach (RentalPayment::where('status', RentalPayment::STATUS_PROCESSING)->latest('id')->limit($limit)->get() as $p) {
            $items[] = ['📦 ' . $p->payment_reference . ' · ' . BusinessAlerts::baht((float) $p->amount), 'r', (int) $p->id];
        }

        return array_slice($items, 0, $limit);
    }

    // ================================================================================ health

    public static function status(): Alert
    {
        $chips = [];
        $facts = [];
        $problems = [];

        $beat = Cache::get('scheduler:heartbeat');
        $beatAge = is_numeric($beat) ? intdiv(time() - (int) $beat, 60) : null;
        $chips['ตัวตั้งเวลา (cron)'] = $beatAge !== null && $beatAge < 30;
        if (! $chips['ตัวตั้งเวลา (cron)']) {
            $problems[] = $beatAge === null ? 'ยังไม่เคยเห็น cron ทำงาน (หรือ cache ถูกล้าง)' : 'cron เงียบมา ' . $beatAge . ' นาที';
        }

        [$queued, $oldest] = self::queueBacklog();
        $chips['คิวงานเบื้องหลัง'] = $oldest === null || $oldest < 15;
        if (! $chips['คิวงานเบื้องหลัง']) {
            $problems[] = 'มีงานในคิวค้าง ' . $queued . ' งาน เก่าสุด ' . $oldest . ' นาที — queue worker อาจหยุด';
        }

        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if ($free && $total) {
            $chips['พื้นที่ดิสก์'] = $free / 1024 ** 3 >= 5 && $free / $total >= 0.05;
            $facts['ดิสก์ว่าง'] = number_format($free / 1024 ** 3, 1) . ' GB';
        }

        try {
            $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
        } catch (Throwable) {
            $failed = 0;
        }
        $errors = DB::table('admin_alerts')->where('category', 'system')->where('created_at', '>=', now()->subDay())->count();

        // Webhook healthy = set, and no delivery error from Telegram in the last hour.
        $hook = TelegramBot::webhookInfo();
        $chips['รับคำสั่งบอท'] = $hook !== null && $hook['url'] !== ''
            && ($hook['last_error_at'] === null || $hook['last_error_at'] < time() - 3600);

        $facts += [
            'งานล้มเหลว 24 ชม.' => number_format($failed),
            'แจ้งเตือนระบบ 24 ชม.' => number_format($errors),
            'คิวค้าง' => number_format($queued),
        ];

        return new Alert(
            key: 'report-status:' . now()->timestamp,
            level: $problems === [] ? Alert::OK : Alert::WARNING,
            title: $problems === [] ? 'ระบบปกติดีทุกอย่าง' : 'ระบบมีเรื่องต้องดู ' . count($problems) . ' เรื่อง',
            body: $problems !== [] ? implode("\n", array_map(fn ($p) => '• ' . $p, $problems)) : 'PHP ' . PHP_VERSION . ' · Laravel ' . app()->version(),
            facts: array_slice($facts, 0, 4, true),
            chips: $chips,
            url: self::url('admin.alerts.index'),
            urlLabel: 'ตั้งค่าการแจ้งเตือน',
            category: 'system',
            chipsLabel: 'สถานะระบบ',
        );
    }

    /**
     * [jobs waiting, minutes the queue has gone without progress]. The database queue records when
     * each job was pushed; Redis (production) does not, so there the watchdog ($track) remembers when
     * a backlog appeared and resets the clock whenever it shrinks — a queue nobody is working stays
     * non-empty and never shrinks.
     *
     * @return array{0:int,1:?int}
     */
    public static function queueBacklog(bool $track = false): array
    {
        $driver = (string) config('queue.default');
        if (in_array($driver, ['sync', 'null'], true)) {
            return [0, null];
        }
        try {
            if ($driver === 'database') {
                $count = DB::table('jobs')->whereNull('reserved_at')->count();
                $oldest = DB::table('jobs')->whereNull('reserved_at')->min('created_at');

                return [$count, $oldest ? intdiv(time() - (int) $oldest, 60) : null];
            }
            $count = (int) Queue::size();
        } catch (Throwable) {
            return [0, null];
        }

        $state = Cache::get('watchdog:queue', null);
        if ($count === 0) {
            $track && Cache::forget('watchdog:queue');

            return [0, null];
        }
        if (! is_array($state) || $count < (int) ($state['size'] ?? 0)) {
            $state = ['since' => time(), 'size' => $count];   // new backlog, or it moved: restart the clock
        }
        $state['size'] = $count;
        $track && Cache::put('watchdog:queue', $state, now()->addDay());

        return [$count, intdiv(time() - (int) $state['since'], 60)];
    }

    public static function security(): Alert
    {
        $today = SecurityAlerts::stats(CarbonImmutable::now(self::TZ));
        $yesterday = SecurityAlerts::stats(CarbonImmutable::now(self::TZ)->subDay());
        $labels = SecurityAlerts::STAT_LABELS;
        $bars = [];
        foreach ($today['counts'] as $k => $n) {
            if ($n > 0) {
                $bars[$labels[$k] ?? $k] = $n;
            }
        }
        $ips = [];
        foreach (array_slice($today['ips'], 0, 5, true) as $ip => $n) {
            $ips[] = '• ' . $ip . ' — ' . $n . ' ครั้ง';
        }
        $total = array_sum($today['counts']);

        return new Alert(
            key: 'report-security:' . now()->timestamp,
            level: ($today['counts']['forged'] ?? 0) > 0 ? Alert::CRITICAL : ($total > 0 ? Alert::INFO : Alert::OK),
            title: $total > 0 ? 'เหตุการณ์ความปลอดภัยวันนี้ ' . number_format($total) . ' ครั้ง' : 'วันนี้ยังไม่พบความผิดปกติ',
            body: $ips !== [] ? "IP ที่พบบ่อยที่สุด:\n" . implode("\n", $ips) : 'ไม่มี IP ที่น่าสงสัย',
            facts: [
                'ล็อกอินผิด' => number_format($today['counts']['failed_login'] ?? 0),
                'เมื่อวาน (รวม)' => number_format(array_sum($yesterday['counts'])),
                'webhook ปลอม' => number_format($today['counts']['forged'] ?? 0),
            ],
            bars: $bars,
            category: 'security',
            barsLabel: 'แยกตามประเภท',
        );
    }

    // ================================================================================ queries

    /** @return array{amount:float,count:int,rentals:int} paid orders + completed rentals in [from, to) */
    public static function sales(CarbonImmutable|\DateTimeInterface $from, CarbonImmutable|\DateTimeInterface $to): array
    {
        $orders = self::paidOrders($from, $to);
        $orderSum = (float) (clone $orders)->sum('total');
        $orderCount = (clone $orders)->count();

        $rentals = RentalPayment::where('status', RentalPayment::STATUS_COMPLETED)
            ->where(fn (Builder $q) => $q->whereBetween('paid_at', [$from, $to])
                ->orWhere(fn (Builder $q) => $q->whereNull('paid_at')->whereBetween('updated_at', [$from, $to])));
        $rentalSum = (float) (clone $rentals)->sum('amount');
        $rentalCount = (clone $rentals)->count();

        return ['amount' => round($orderSum + $rentalSum, 2), 'count' => $orderCount + $rentalCount, 'rentals' => $rentalCount];
    }

    private static function paidOrders($from, $to): Builder
    {
        return Order::whereIn('payment_status', ['paid', 'confirmed'])
            ->where(fn (Builder $q) => $q->whereBetween('paid_at', [$from, $to])
                ->orWhere(fn (Builder $q) => $q->whereNull('paid_at')->whereBetween('created_at', [$from, $to])));
    }

    /** @return array<string,float> product name => revenue, top 5 */
    private static function topProducts($from, $to): array
    {
        try {
            $ids = self::paidOrders($from, $to)->pluck('id');
            if ($ids->isEmpty()) {
                return [];
            }

            return DB::table('order_items')->whereIn('order_id', $ids)
                ->select('product_name', DB::raw('SUM(subtotal) as revenue'))
                ->groupBy('product_name')->orderByDesc('revenue')->limit(5)
                ->pluck('revenue', 'product_name')
                ->mapWithKeys(fn ($v, $k) => [mb_substr((string) $k, 0, 40) => (float) $v])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    // ================================================================================ helpers

    /** " (+12%)" against the period before — omitted when there is nothing to compare with. */
    private static function trend(float $now, float $before): string
    {
        if ($before <= 0) {
            return '';
        }
        $pct = (int) round(($now - $before) / $before * 100);

        return $pct === 0 ? ' (=)' : ' (' . ($pct > 0 ? '+' : '') . $pct . '%)';
    }

    public static function thaiDate(CarbonImmutable $d): string
    {
        return $d->day . ' ' . self::MONTHS[$d->month - 1] . ' ' . ($d->year + 543);
    }

    private static function url(string $name): ?string
    {
        try {
            return route($name);
        } catch (Throwable) {
            return url('/admin');
        }
    }

    /** Send a report card to a chat now (bot commands) — not throttled, not category-gated. */
    public static function deliver(Alert $alert, string $chat, ?int $thread = null, ?int $replyTo = null): ?string
    {
        TelegramBot::chatAction($chat, 'upload_photo', $thread);

        return TelegramBot::sendAlert($alert, $chat, $thread, $replyTo)['error'];
    }
}
