<?php

namespace App\Services;

/**
 * What a Whisper fine-tuning run on a rented GPU should cost, before renting.
 *
 * Whisper pads every clip to 30 seconds, so compute follows the number of
 * samples, not the hours of audio. Throughput per model comes from
 * config('services.aipray_gpu.throughput'); those numbers are assumptions
 * until measured on a real run, hence the wide low/high band.
 */
class AiprayTrainingCostEstimator
{
    /** Validation (generation for WER) costs roughly this share on top of training. */
    private const VALIDATION_SHARE = 0.15;

    /** ffmpeg decode per clip, in seconds, spread over this many parallel workers. */
    private const DECODE_SECONDS_PER_CLIP = 0.05;

    private const DECODE_WORKERS = 8;

    /**
     * @return array{gpu_hours: float, audio_hours: float, price_per_hour: float, low_usd: float, expected_usd: float, high_usd: float, samples_per_second: int}
     */
    public function estimate(int $samples, float $audioSeconds, string $baseModel, int $epochs, ?float $pricePerHour = null): array
    {
        $config = config('services.aipray_gpu');
        $perSecond = (int) ($config['throughput'][$baseModel] ?? $config['throughput']['whisper-small']);
        $price = $pricePerHour ?? (float) $config['expected_price_per_hour'];

        $trainSeconds = $samples * max(1, $epochs) / max(1, $perSecond) * (1 + self::VALIDATION_SHARE);
        $decodeSeconds = $samples * self::DECODE_SECONDS_PER_CLIP / self::DECODE_WORKERS;
        $hours = ($trainSeconds + $decodeSeconds + $config['overhead_minutes'] * 60) / 3600;

        return [
            'gpu_hours' => round($hours, 2),
            'audio_hours' => round($audioSeconds / 3600, 2),
            'price_per_hour' => round($price, 3),
            'low_usd' => round($hours * 0.7 * $price, 2),
            'expected_usd' => round($hours * $price, 2),
            'high_usd' => round($hours * 1.6 * $price, 2),
            'samples_per_second' => $perSecond,
        ];
    }
}
