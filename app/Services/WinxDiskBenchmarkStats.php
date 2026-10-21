<?php

namespace App\Services;

use App\Models\WinxDiskBenchmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * What WinXTools users measured on one drive model — the answer behind
 * GET /api/v1/product/winx-tools/disk-benchmarks/stats.
 *
 * Built from the newest MAX_ROWS uploads of the model, with the bottom and top 5% by score
 * dropped once there are enough of them (a dying drive or a run disturbed by a virus scan is
 * not "normal"). Nothing is described until MIN_DEVICES different installations stand behind
 * it, so no number on the screen is one person's result — and every count, that one included,
 * is taken on the rows the numbers are actually computed from, after the trim.
 *
 * Percentiles interpolate linearly between closest ranks (Excel's PERCENTILE.INC, NumPy's
 * default), in PHP: the query stays portable between SQLite (dev) and MySQL (production).
 */
class WinxDiskBenchmarkStats
{
    /** Installations needed before anything is described. */
    public const MIN_DEVICES = 3;

    /** The newest uploads of a model that a summary is built from. */
    public const MAX_ROWS = 2000;

    /** From this many rows on, those scoring below the 5th or above the 95th percentile are dropped. */
    public const TRIM_FROM_ROWS = 20;

    public const CACHE_SECONDS = 600;

    /** Described by quartiles, next to score_total. */
    public const METRICS = [
        'seq1m_q8_read',
        'seq1m_q8_write',
        'seq1m_q1_read',
        'rnd4k_q32_read',
        'rnd4k_q1_read',
        'rnd4k_q1_write',
        'access_ms',
    ];

    public const SURFACE_POINTS = 20;

    /**
     * @return array{samples: int, devices: int, stats: array<string, mixed>|null}
     */
    public function forModel(string $modelKey, ?int $capacityGb = null): array
    {
        if ($modelKey === '') {
            return $this->summarize(collect());
        }

        return Cache::remember(
            'winx-disk-bench:stats:' . $modelKey . ':' . ($capacityGb ?? 'any'),
            self::CACHE_SECONDS,
            fn () => $this->summarize($this->newestRows($modelKey, $capacityGb)),
        );
    }

    /**
     * Capacities within ±10% of $gb, both ends included. Integer arithmetic: 0.9 * $gb in floating
     * point can land a hair above a whole number and ceil() would then lose that capacity.
     *
     * @return array{0: int, 1: int}
     */
    public static function capacityRange(int $gb): array
    {
        return [intdiv($gb * 9 + 9, 10), intdiv($gb * 11, 10)];
    }

    /**
     * The value below which $percent% of $sorted lies, interpolated between closest ranks.
     *
     * @param  list<float>  $sorted  ascending, at least one value
     */
    public static function percentile(array $sorted, int $percent): float
    {
        $last = count($sorted) - 1;
        $position = $last * $percent / 100;
        $below = (int) floor($position);
        $above = min($below + 1, $last);

        return $sorted[$below] + ($sorted[$above] - $sorted[$below]) * ($position - $below);
    }

    /**
     * @param  Collection<int, WinxDiskBenchmark>  $rows
     * @return array{samples: int, devices: int, stats: array<string, mixed>|null}
     */
    public function summarize(Collection $rows): array
    {
        $rows = $this->withoutOutliers($rows);
        $devices = self::installations($rows);

        return [
            'samples' => $rows->count(),
            'devices' => $devices,
            'stats' => $devices >= self::MIN_DEVICES ? $this->describe($rows) : null,
        ];
    }

    /**
     * @return Collection<int, WinxDiskBenchmark>
     */
    private function newestRows(string $modelKey, ?int $capacityGb): Collection
    {
        $query = WinxDiskBenchmark::query()->where('model_key', $modelKey);

        if ($capacityGb !== null) {
            $query->whereBetween('capacity_gb', self::capacityRange($capacityGb));
        }

        return $query->orderByDesc('id')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'install_id', 'score_total', ...self::METRICS, 'surface_points']);
    }

    /**
     * @param  Collection<int, WinxDiskBenchmark>  $rows
     * @return Collection<int, WinxDiskBenchmark>
     */
    private function withoutOutliers(Collection $rows): Collection
    {
        if ($rows->count() < self::TRIM_FROM_ROWS) {
            return $rows;
        }

        $scores = self::sorted($rows->pluck('score_total'));
        $low = self::percentile($scores, 5);
        $high = self::percentile($scores, 95);

        return $rows
            ->filter(fn (WinxDiskBenchmark $row) => $row->score_total >= $low && $row->score_total <= $high)
            ->values();
    }

    /**
     * @param  Collection<int, WinxDiskBenchmark>  $rows  at least MIN_DEVICES installations
     * @return array<string, mixed>
     */
    private function describe(Collection $rows): array
    {
        $scores = self::sorted($rows->pluck('score_total'));
        $stats = ['score_total' => self::quartiles($scores) + ['max' => round(end($scores), 1)]];

        foreach (self::METRICS as $metric) {
            $measured = $rows->filter(fn (WinxDiskBenchmark $row) => $row->{$metric} !== null);

            // The same rule as the whole answer, per metric: a test only some PCs ran (writes are
            // optional; access time is an HDD number) is left out until three installations ran it
            // — which also means at least three values.
            if (self::installations($measured) < self::MIN_DEVICES) {
                continue;
            }

            $stats[$metric] = self::quartiles(self::sorted($measured->pluck($metric)));
        }

        $stats['surface_median'] = $this->surfaceMedian($rows);

        return $stats;
    }

    /**
     * The median of each of the 20 surface points, or null until three installations sent a surface.
     *
     * @param  Collection<int, WinxDiskBenchmark>  $rows
     * @return list<float>|null
     */
    private function surfaceMedian(Collection $rows): ?array
    {
        $surfaces = $rows->filter(
            fn (WinxDiskBenchmark $row) => is_array($row->surface_points)
                && array_is_list($row->surface_points)
                && count($row->surface_points) === self::SURFACE_POINTS
        );

        if (self::installations($surfaces) < self::MIN_DEVICES) {
            return null;
        }

        $medians = [];

        for ($i = 0; $i < self::SURFACE_POINTS; $i++) {
            $point = self::sorted($surfaces->map(fn (WinxDiskBenchmark $row) => $row->surface_points[$i]));
            $medians[] = round(self::percentile($point, 50), 1);
        }

        return $medians;
    }

    /**
     * @param  Collection<int, WinxDiskBenchmark>  $rows
     */
    private static function installations(Collection $rows): int
    {
        return $rows->pluck('install_id')->unique()->count();
    }

    /**
     * @param  list<float>  $sorted
     * @return array{p25: float, median: float, p75: float}
     */
    private static function quartiles(array $sorted): array
    {
        return [
            'p25' => round(self::percentile($sorted, 25), 1),
            'median' => round(self::percentile($sorted, 50), 1),
            'p75' => round(self::percentile($sorted, 75), 1),
        ];
    }

    /**
     * @param  iterable<mixed>  $values  numbers or numeric strings (decimal columns come back as strings)
     * @return list<float>
     */
    private static function sorted(iterable $values): array
    {
        $floats = [];

        foreach ($values as $value) {
            $floats[] = (float) $value;
        }

        sort($floats);

        return $floats;
    }
}
