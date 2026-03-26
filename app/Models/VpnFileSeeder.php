<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnFileSeeder extends Model
{
    protected $fillable = [
        'shared_file_id',
        'member_id',
        'chunks_bitmap',
    ];

    public function sharedFile(): BelongsTo
    {
        return $this->belongsTo(VpnSharedFile::class, 'shared_file_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(VpnNetworkMember::class, 'member_id');
    }

    public function hasAllChunks(): bool
    {
        return $this->chunks_bitmap === 'all';
    }
}
