<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCrawlLog extends Model
{
    protected $fillable = [
        'bot_name',
        'bot_category',
        'ip_address',
        'url',
        'method',
        'status_code',
        'user_agent',
        'was_blocked',
    ];

    protected $casts = [
        'was_blocked' => 'boolean',
    ];

    public function scopeBot($query, string $botName)
    {
        return $query->where('bot_name', $botName);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('bot_category', $category);
    }

    public function scopeBlocked($query)
    {
        return $query->where('was_blocked', true);
    }

    public function scopeAllowed($query)
    {
        return $query->where('was_blocked', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }
}
