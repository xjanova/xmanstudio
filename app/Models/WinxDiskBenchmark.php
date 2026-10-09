<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One disk speed test that a WinXTools installation uploaded.
 *
 * Anonymous by design — see the migration. Written by WinxDiskBenchmarkController and summed up
 * per drive model by App\Services\WinxDiskBenchmarkStats.
 */
class WinxDiskBenchmark extends Model
{
    protected $fillable = [
        'install_id',
        'app_version',
        'os_build',
        'model',
        'model_key',
        'kind',
        'bus',
        'media',
        'capacity_gb',
        'firmware',
        'rpm',
        'file_mb',
        'seconds_per_test',
        'label',
        'free_pct',
        'seq1m_q8_read',
        'seq1m_q8_write',
        'seq1m_q1_read',
        'seq1m_q1_write',
        'rnd4k_q32_read',
        'rnd4k_q32_write',
        'rnd4k_q1_read',
        'rnd4k_q1_write',
        'rnd4k_q32_read_iops',
        'rnd4k_q1_read_iops',
        'access_ms',
        'surface_min',
        'surface_avg',
        'surface_max',
        'surface_points',
        'score_total',
        'score_read',
        'score_write',
    ];

    protected $casts = [
        'os_build' => 'integer',
        'capacity_gb' => 'integer',
        'rpm' => 'integer',
        'file_mb' => 'integer',
        'seconds_per_test' => 'integer',
        'free_pct' => 'decimal:2',
        'seq1m_q8_read' => 'decimal:2',
        'seq1m_q8_write' => 'decimal:2',
        'seq1m_q1_read' => 'decimal:2',
        'seq1m_q1_write' => 'decimal:2',
        'rnd4k_q32_read' => 'decimal:2',
        'rnd4k_q32_write' => 'decimal:2',
        'rnd4k_q1_read' => 'decimal:2',
        'rnd4k_q1_write' => 'decimal:2',
        'rnd4k_q32_read_iops' => 'integer',
        'rnd4k_q1_read_iops' => 'integer',
        'access_ms' => 'decimal:3',
        'surface_min' => 'decimal:2',
        'surface_avg' => 'decimal:2',
        'surface_max' => 'decimal:2',
        'surface_points' => 'array',
        'score_total' => 'integer',
        'score_read' => 'integer',
        'score_write' => 'integer',
    ];

    /**
     * The key a model name is matched on: uppercase ASCII letters and digits only, so the
     * spellings Windows and drivers give one drive ("WD_BLACK SN770 1TB", "WD BLACK SN770 1TB")
     * meet. '' when the name holds no letter or digit at all.
     */
    public static function keyFor(string $model): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($model));
    }
}
