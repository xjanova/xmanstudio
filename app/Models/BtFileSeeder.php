<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtFileSeeder extends Model
{
    protected $fillable = [
        'bt_file_id',
        'machine_id',
        'display_name',
        'public_ip',
        'public_port',
        'is_online',
        'last_seen_at',
        'chunks_bitmap',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'public_port' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(BtFile::class, 'bt_file_id');
    }

    public function hasAllChunks(): bool
    {
        return $this->chunks_bitmap === 'all';
    }
}
