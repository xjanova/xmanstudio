<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtUserTrophy extends Model
{
    protected $fillable = [
        'machine_id',
        'trophy_id',
        'awarded_at',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function trophy(): BelongsTo
    {
        return $this->belongsTo(BtTrophy::class, 'trophy_id');
    }
}
