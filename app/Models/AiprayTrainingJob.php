<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiprayTrainingJob extends Model
{
    protected $table = 'aipray_training_jobs';

    protected $fillable = [
        'name', 'base_model', 'dataset_filter', 'learning_rate', 'batch_size',
        'epochs', 'current_epoch', 'train_split', 'optimizer', 'augmentation',
        'status', 'training_loss', 'validation_loss', 'wer', 'cer', 'accuracy',
        'loss_history', 'metrics_history', 'log', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'augmentation' => 'array',
        'loss_history' => 'array',
        'metrics_history' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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
