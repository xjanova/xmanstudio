<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductWorkflow extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'name',
        'target_app_package',
        'target_app_name',
        'steps_json',
        'device_id',
        'app_version',
        'is_public',
        'share_token',
        'shared_at',
        'local_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'shared_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function generateShareToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'share_token' => $token,
            'is_public' => true,
            'shared_at' => now(),
        ]);

        return $token;
    }

    public function revokeShare(): void
    {
        $this->update([
            'share_token' => null,
            'is_public' => false,
            'shared_at' => null,
        ]);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->whereNotNull('share_token');
    }
}
