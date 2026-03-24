<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiprayAiModel extends Model
{
    protected $table = 'aipray_ai_models';

    protected $fillable = [
        'name', 'version', 'base_model', 'training_job_id', 'file_path',
        'file_size', 'accuracy', 'wer', 'cer', 'total_samples_trained',
        'total_hours_trained', 'onnx_file_path', 'eval_results', 'status', 'notes',
    ];

    protected $casts = [
        'eval_results' => 'array',
    ];

    public function trainingJob(): BelongsTo
    {
        return $this->belongsTo(AiprayTrainingJob::class, 'training_job_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(AiprayEvaluation::class, 'ai_model_id');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}
