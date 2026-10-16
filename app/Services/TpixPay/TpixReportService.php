<?php

namespace App\Services\TpixPay;

use App\Models\TpixPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the admin "รายงาน TPIX" page: what came in, how quotes turn into payments, the price
 * and the pool over the last week. Every total is one SQL aggregate; the whole set is cached for a
 * minute so a dashboard left open (or several admins) does not re-run them on every refresh.
 *
 * Days are Bangkok days (the app clock is UTC): "today" for the shop starts at 00:00 ICT, and the
 * daily chart groups paid_at shifted by the zone's offset.
 */
class TpixReportService
{
    public const TIMEZONE = 'Asia/Bangkok';

    public const CACHE_KEY = 'tpix-report:v1';

    public const CACHE_SECONDS = 60;

    /** Points a line chart gets at most — the snapshot table holds one row a minute (≈10k a week). */
    public const MAX_POINTS = 300;

    /** Window of the moving-average line on the price chart, in seconds. */
    public const MOVING_AVERAGE_SECONDS = 1800;

    /** Statuses that mean the coins arrived and were applied (to the order/top-up or the wallet). */
    public const SETTLED = [TpixPayment::STATUS_PAID, TpixPayment::STATUS_CREDITED_WALLET];

    /** @return array<string, mixed> */
    public function report(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->build());
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $now = CarbonImmutable::now();
        $todayStart = $now->setTimezone(self::TIMEZONE)->startOfDay()->utc();
        $since7 = $todayStart->subDays(6);
        $since30 = $todayStart->subDays(29);

        return [
            'generated_at' => $now->setTimezone(self::TIMEZONE)->format('d/m/Y H:i:s'),
            'kpis' => $this->kpis($todayStart, $since7, $since30),
            'daily' => $this->daily($since30),
            'price' => $this->priceSeries($now->subDays(7)),
            'statuses' => $this->statuses($since30),
            'top_payers' => $this->topPayers($since30),
        ];
    }

    /** @return array<string, mixed> */
    private function kpis(CarbonImmutable $todayStart, CarbonImmutable $since7, CarbonImmutable $since30): array
    {
        $received = self::receivedThbSql();
        $row = DB::table('tpix_payments')
            ->whereIn('status', self::SETTLED)
            ->where('paid_at', '>=', $since30)
            ->selectRaw(
                "SUM(CASE WHEN paid_at >= ? THEN {$received} ELSE 0 END) AS thb_today,
                 SUM(CASE WHEN paid_at >= ? THEN amount_tpix ELSE 0 END) AS tpix_today,
                 SUM(CASE WHEN paid_at >= ? THEN 1 ELSE 0 END) AS n_today,
                 SUM(CASE WHEN paid_at >= ? THEN {$received} ELSE 0 END) AS thb_7d,
                 SUM(CASE WHEN paid_at >= ? THEN amount_tpix ELSE 0 END) AS tpix_7d,
                 SUM(CASE WHEN paid_at >= ? THEN 1 ELSE 0 END) AS n_7d,
                 SUM({$received}) AS thb_30d,
                 SUM(amount_tpix) AS tpix_30d,
                 COUNT(*) AS n_30d,
                 SUM(CASE WHEN status = ? THEN COALESCE(credited_thb, 0) ELSE 0 END) AS credited_thb_30d,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS credited_n_30d",
                [$todayStart, $todayStart, $todayStart, $since7, $since7, $since7, TpixPayment::STATUS_CREDITED_WALLET, TpixPayment::STATUS_CREDITED_WALLET]
            )
            ->first();

        $quotes30 = (int) DB::table('tpix_payments')->where('created_at', '>=', $since30)->count();
        $settledOf30 = (int) DB::table('tpix_payments')->where('created_at', '>=', $since30)->whereIn('status', self::SETTLED)->count();

        $waiting = DB::table('tpix_payments')
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND expires_at > ? THEN 1 ELSE 0 END) AS open_pending,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS confirming,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS held,
                 SUM(CASE WHEN status = ? THEN amount_thb ELSE 0 END) AS held_thb,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS late',
                [TpixPayment::STATUS_PENDING, now(), TpixPayment::STATUS_CONFIRMING, TpixPayment::STATUS_HELD, TpixPayment::STATUS_HELD, TpixPayment::STATUS_LATE]
            )
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING, TpixPayment::STATUS_HELD, TpixPayment::STATUS_LATE])
            ->first();

        $n30 = (int) ($row->n_30d ?? 0);
        $thb30 = (float) ($row->thb_30d ?? 0);

        return [
            'today' => ['thb' => (float) ($row->thb_today ?? 0), 'tpix' => (float) ($row->tpix_today ?? 0), 'count' => (int) ($row->n_today ?? 0)],
            'd7' => ['thb' => (float) ($row->thb_7d ?? 0), 'tpix' => (float) ($row->tpix_7d ?? 0), 'count' => (int) ($row->n_7d ?? 0)],
            'd30' => ['thb' => $thb30, 'tpix' => (float) ($row->tpix_30d ?? 0), 'count' => $n30],
            'average_thb' => $n30 > 0 ? round($thb30 / $n30, 2) : 0.0,
            'quotes_30d' => $quotes30,
            'settled_of_quotes_30d' => $settledOf30,
            'conversion_pct' => $quotes30 > 0 ? round($settledOf30 * 100 / $quotes30, 1) : null,
            'open_quotes' => (int) ($waiting->open_pending ?? 0) + (int) ($waiting->confirming ?? 0),
            'confirming' => (int) ($waiting->confirming ?? 0),
            'held' => (int) ($waiting->held ?? 0),
            'held_thb' => (float) ($waiting->held_thb ?? 0),
            'late' => (int) ($waiting->late ?? 0),
            'credited_thb_30d' => (float) ($row->credited_thb_30d ?? 0),
            'credited_count_30d' => (int) ($row->credited_n_30d ?? 0),
        ];
    }

    /**
     * THB received per Bangkok day and purpose for the last 30 days, every day present (zeros too).
     *
     * @return array{labels: list<string>, series: array<string, list<float>>}
     */
    private function daily(CarbonImmutable $since30): array
    {
        $day = self::localDateSql('paid_at');
        $received = self::receivedThbSql();

        $rows = DB::table('tpix_payments')
            ->whereIn('status', self::SETTLED)
            ->where('paid_at', '>=', $since30)
            ->selectRaw("{$day} AS d, purpose, SUM({$received}) AS thb")
            ->groupByRaw("{$day}, purpose")
            ->get();

        $keys = [];
        $labels = [];
        $start = $since30->setTimezone(self::TIMEZONE);
        for ($i = 0; $i < 30; $i++) {
            $date = $start->addDays($i);
            $keys[] = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
        }

        $series = [];
        foreach (TpixPayment::PURPOSES as $purpose) {
            $series[$purpose] = array_fill_keys($keys, 0.0);
        }
        foreach ($rows as $row) {
            $purpose = in_array($row->purpose, TpixPayment::PURPOSES, true) ? $row->purpose : TpixPayment::PURPOSE_ORDER;
            $d = substr((string) $row->d, 0, 10);
            if (isset($series[$purpose][$d])) {
                $series[$purpose][$d] += round((float) $row->thb, 2);
            }
        }

        return ['labels' => $labels, 'series' => array_map('array_values', $series)];
    }

    /**
     * Price (THB and USD), its 30-minute trailing average, and the pool's reserves since $since,
     * cut down to at most MAX_POINTS by averaging equal time buckets. The moving average is taken on
     * the full-resolution series first, so downsampling does not change what it means.
     *
     * @return array<string, list<float|string|null>>
     */
    private function priceSeries(CarbonImmutable $since): array
    {
        $rows = DB::table('tpix_price_snapshots')
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get(['created_at', 'price_usd', 'price_thb', 'reserve_tpix', 'reserve_usdt']);

        $points = [];
        foreach ($rows as $row) {
            $points[] = [
                't' => CarbonImmutable::parse($row->created_at, 'UTC')->getTimestamp(),
                'usd' => $row->price_usd !== null ? (float) $row->price_usd : null,
                'thb' => $row->price_thb !== null ? (float) $row->price_thb : null,
                'rt' => $row->reserve_tpix !== null ? (float) $row->reserve_tpix : null,
                'ru' => $row->reserve_usdt !== null ? (float) $row->reserve_usdt : null,
            ];
        }

        // Trailing 30-minute average of the THB price (two pointers over the time-ordered rows)
        $sum = 0.0;
        $n = 0;
        $tail = 0;
        foreach ($points as $i => $p) {
            if ($p['thb'] !== null) {
                $sum += $p['thb'];
                $n++;
            }
            while ($points[$tail]['t'] <= $p['t'] - self::MOVING_AVERAGE_SECONDS) {
                if ($points[$tail]['thb'] !== null) {
                    $sum -= $points[$tail]['thb'];
                    $n--;
                }
                $tail++;
            }
            $points[$i]['ma'] = $n > 0 ? $sum / $n : null;
        }

        $points = self::downsample($points, self::MAX_POINTS);
        $tz = self::TIMEZONE;

        return [
            'labels' => array_map(fn ($p) => CarbonImmutable::createFromTimestamp($p['t'], $tz)->format('d/m H:i'), $points),
            'thb' => array_map(fn ($p) => self::round($p['thb'], 6), $points),
            'thb_ma30' => array_map(fn ($p) => self::round($p['ma'], 6), $points),
            'usd' => array_map(fn ($p) => self::round($p['usd'], 8), $points),
            'reserve_tpix' => array_map(fn ($p) => self::round($p['rt'], 2), $points),
            'reserve_usdt' => array_map(fn ($p) => self::round($p['ru'], 2), $points),
        ];
    }

    /**
     * Average equal-time buckets until at most $max points remain (fewer points pass through).
     *
     * @param  list<array<string, int|float|null>>  $points  time-ordered, each with 't'
     * @return list<array<string, int|float|null>>
     */
    public static function downsample(array $points, int $max): array
    {
        $count = count($points);
        if ($count <= $max || $max < 1) {
            return $points;
        }

        $first = $points[0]['t'];
        $span = max(1, $points[$count - 1]['t'] - $first + 1);
        $buckets = [];

        foreach ($points as $p) {
            $b = min($max - 1, intdiv(($p['t'] - $first) * $max, $span));
            foreach ($p as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $buckets[$b][$key][0] = ($buckets[$b][$key][0] ?? 0) + $value;
                $buckets[$b][$key][1] = ($buckets[$b][$key][1] ?? 0) + 1;
            }
        }

        ksort($buckets);
        $out = [];
        $keys = array_keys($points[0]);
        foreach ($buckets as $bucket) {
            $point = [];
            foreach ($keys as $key) {
                $point[$key] = isset($bucket[$key]) ? $bucket[$key][0] / $bucket[$key][1] : null;
            }
            $point['t'] = (int) round($point['t']);
            $out[] = $point;
        }

        return $out;
    }

    /** @return array<string, int> quotes made in the last 30 days, by status */
    private function statuses(CarbonImmutable $since30): array
    {
        return DB::table('tpix_payments')
            ->where('created_at', '>=', $since30)
            ->selectRaw('status, COUNT(*) AS n')
            ->groupBy('status')
            ->orderByDesc('n')
            ->pluck('n', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function topPayers(CarbonImmutable $since30): array
    {
        $received = self::receivedThbSql('p');

        return DB::table('tpix_payments as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->whereIn('p.status', self::SETTLED)
            ->where('p.paid_at', '>=', $since30)
            ->groupBy('p.user_id', 'u.name', 'u.email')
            ->selectRaw("p.user_id, u.name, u.email, COUNT(*) AS n, SUM({$received}) AS thb, SUM(p.amount_tpix) AS tpix, MAX(p.paid_at) AS last_paid")
            ->orderByDesc('thb')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'user_id' => (int) $r->user_id,
                'name' => (string) ($r->name ?? '—'),
                'email' => (string) ($r->email ?? ''),
                'count' => (int) $r->n,
                'thb' => round((float) $r->thb, 2),
                'tpix' => (float) $r->tpix,
                'last_paid' => $r->last_paid ? CarbonImmutable::parse($r->last_paid, 'UTC')->setTimezone(self::TIMEZONE)->format('d/m/Y H:i') : null,
            ])
            ->all();
    }

    /** THB a settled row brought in: the quote's THB, or what actually went to the wallet. */
    public static function receivedThbSql(string $table = ''): string
    {
        $c = $table !== '' ? $table . '.' : '';

        return "CASE WHEN {$c}status = '" . TpixPayment::STATUS_CREDITED_WALLET . "' THEN COALESCE({$c}credited_thb, {$c}amount_thb) ELSE {$c}amount_thb END";
    }

    /** The Bangkok calendar date of a UTC timestamp column, in this connection's SQL dialect. */
    public static function localDateSql(string $column): string
    {
        $minutes = (int) CarbonImmutable::now(self::TIMEZONE)->utcOffset();

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date({$column}, '{$minutes} minutes')",
            'pgsql' => "date({$column} + interval '{$minutes} minutes')",
            default => "DATE(DATE_ADD({$column}, INTERVAL {$minutes} MINUTE))",
        };
    }

    private static function round(?float $value, int $digits): ?float
    {
        return $value === null ? null : round($value, $digits);
    }
}
