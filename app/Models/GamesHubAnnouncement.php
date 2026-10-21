<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A notice shown across the top of the XGamesHub site (read through /games-support/hub.json). */
class GamesHubAnnouncement extends Model
{
    public const TONES = ['info' => 'ข่าวทั่วไป', 'event' => 'กิจกรรม / ของใหม่', 'warning' => 'แจ้งปัญหา / ปิดปรับปรุง'];

    protected $table = 'gameshub_announcements';

    protected $fillable = ['message', 'link_url', 'link_label', 'tone', 'starts_at', 'ends_at', 'active', 'created_by'];

    protected $casts = ['active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isLive(): bool
    {
        return $this->active && (! $this->starts_at || $this->starts_at->lte(now())) && (! $this->ends_at || $this->ends_at->gt(now()));
    }
}
