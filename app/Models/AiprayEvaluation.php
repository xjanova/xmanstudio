<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiprayEvaluation extends Model
{
    protected $table = 'aipray_evaluations';

    protected $fillable = [
        'ai_model_id', 'eval_type', 'recognized_text', 'reference_text',
        'accuracy', 'wer', 'cer', 'latency_ms', 'audio_file', 'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiprayAiModel::class, 'ai_model_id');
    }
}
