<?php

namespace App\Services;

use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use Illuminate\Support\Facades\DB;

/**
 * Whether there is enough donated voice to be worth renting a GPU.
 *
 * The chants are fixed text, so a model learns them from hearing every line
 * in many voices. Three numbers decide it: trainable samples, distinct
 * contributors (app 1.2.5+ installs), and the share of chant lines that have
 * at least `min_samples_per_line` samples. Thresholds live in
 * config('services.aipray_gpu.readiness').
 */
class AiprayDatasetReadiness
{
    /**
     * @return array{ready: bool, samples: int, seconds: float, contributors: int, lines_total: int, lines_covered: int, coverage: float, missing: string[], targets: array}
     */
    public function check(): array
    {
        $targets = config('services.aipray_gpu.readiness');
        $trainable = AiprayAudioSample::trainable();

        $samples = (clone $trainable)->count();
        $seconds = (float) (clone $trainable)->sum('duration');
        $contributors = (clone $trainable)->whereNotNull('contributor_id')
            ->distinct()->count('contributor_id');

        $lineCounts = AiprayChant::active()->pluck('lines', 'chant_id')
            ->map(fn ($lines) => count((array) $lines));
        $covered = (clone $trainable)
            ->select('chant_id', 'line_index', DB::raw('count(*) as n'))
            ->groupBy('chant_id', 'line_index')
            ->havingRaw('count(*) >= ?', [$targets['min_samples_per_line']])
            ->get()
            ->filter(fn ($row) => $row->line_index >= 0 && $row->line_index < ($lineCounts[$row->chant_id] ?? 0))
            ->count();
        $total = (int) $lineCounts->sum();
        $coverage = $total > 0 ? $covered / $total : 0.0;

        $missing = [];
        if ($total === 0) {
            $missing[] = 'ยังไม่ได้นำเข้าบทสวด (php artisan aipray:import-chants)';
        }
        if ($samples < $targets['min_samples']) {
            $missing[] = "เสียงที่ใช้เทรนได้ {$samples}/{$targets['min_samples']} ชิ้น";
        }
        if ($contributors < $targets['min_contributors']) {
            $missing[] = "ผู้ร่วมบริจาคเสียง {$contributors}/{$targets['min_contributors']} คน";
        }
        if ($total > 0 && $coverage < $targets['min_line_coverage']) {
            $missing[] = sprintf(
                'บรรทัดที่มีเสียงอย่างน้อย %d ชิ้น %d/%d (%d%%, ต้องการ %d%%)',
                $targets['min_samples_per_line'], $covered, $total,
                (int) floor($coverage * 100), (int) round($targets['min_line_coverage'] * 100),
            );
        }

        return [
            'ready' => $missing === [],
            'samples' => $samples,
            'seconds' => $seconds,
            'contributors' => $contributors,
            'lines_total' => $total,
            'lines_covered' => $covered,
            'coverage' => round($coverage, 3),
            'missing' => $missing,
            'targets' => $targets,
        ];
    }
}
