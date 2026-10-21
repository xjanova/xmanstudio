<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiprayTrainingJob extends Model
{
    protected $table = 'aipray_training_jobs';

    /** GPU states in which a machine may exist and is being paid for. */
    public const GPU_ACTIVE_STATES = ['queued', 'booting', 'training', 'collecting', 'cancelling'];

    protected $fillable = [
        'name', 'base_model', 'dataset_filter', 'learning_rate', 'batch_size',
        'epochs', 'current_epoch', 'train_split', 'optimizer', 'augmentation',
        'status', 'training_loss', 'validation_loss', 'wer', 'cer', 'accuracy',
        'loss_history', 'metrics_history', 'log', 'started_at', 'completed_at',
        'runner', 'gpu_state', 'gpu_instance_id', 'gpu_offer', 'gpu_endpoint', 'gpu_token',
        'gpu_price_per_hour', 'gpu_rented_at', 'gpu_ready_at', 'gpu_deadline_at',
        'gpu_terminated_at', 'gpu_last_seen_at', 'estimated_cost_usd', 'max_cost_usd',
        'actual_cost_usd', 'dataset_path', 'dataset_samples', 'dataset_seconds', 'artifact_path',
    ];

    protected $hidden = ['gpu_token'];

    protected $casts = [
        'augmentation' => 'array',
        'loss_history' => 'array',
        'metrics_history' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'gpu_token' => 'encrypted',
        'gpu_price_per_hour' => 'float',
        'gpu_rented_at' => 'datetime',
        'gpu_ready_at' => 'datetime',
        'gpu_deadline_at' => 'datetime',
        'gpu_terminated_at' => 'datetime',
        'gpu_last_seen_at' => 'datetime',
        'estimated_cost_usd' => 'float',
        'max_cost_usd' => 'float',
        'actual_cost_usd' => 'float',
    ];

    public function scopeGpuActive($query)
    {
        return $query->where('runner', 'gpu')->whereIn('gpu_state', self::GPU_ACTIVE_STATES);
    }

    /** What the machine has cost so far, at the rented price. */
    public function gpuCostSoFar(): float
    {
        if (! $this->gpu_rented_at || ! $this->gpu_price_per_hour) {
            return 0.0;
        }
        $end = $this->gpu_terminated_at ?? now();

        return round(abs($this->gpu_rented_at->diffInSeconds($end)) / 3600 * $this->gpu_price_per_hour, 2);
    }

    public function aiModel(): HasOne
    {
        return $this->hasOne(AiprayAiModel::class, 'training_job_id');
    }

    public function getProgressAttribute(): float
    {
        if ($this->epochs <= 0) {
            return 0;
        }

        return round(($this->current_epoch / $this->epochs) * 100, 1);
    }

    public function getElapsedAttribute(): string
    {
        if (! $this->started_at) {
            return '00:00:00';
        }
        $end = $this->completed_at ?? now();

        return $this->started_at->diff($end)->format('%H:%I:%S');
    }
}
