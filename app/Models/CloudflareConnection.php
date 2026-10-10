<?php

namespace App\Models;

use App\Services\CloudflareApiService;
use Illuminate\Database\Eloquent\Model;

/**
 * One customer's Cloudflare account, reached with an API token they created.
 *
 * The token never leaves the server again once saved: it is encrypted at
 * rest, hidden from serialisation, and the page shows only its last four
 * characters so the customer can tell which token is in use.
 */
class CloudflareConnection extends Model
{
    protected $fillable = [
        'user_id',
        'api_token',
        'token_hint',
        'account_id',
        'account_name',
        'verified_at',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['api_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(?int $userId): ?self
    {
        return $userId ? static::where('user_id', $userId)->first() : null;
    }

    public function api(): CloudflareApiService
    {
        return new CloudflareApiService($this->api_token);
    }
}
