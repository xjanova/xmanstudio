<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BtKycRequest extends Model
{
    protected $fillable = [
        'machine_id',
        'display_name',
        'id_card_front_path',
        'id_card_back_path',
        'selfie_path',
        'birth_date',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the user is 18 years or older based on birth_date.
     */
    public function isAdult(): bool
    {
        if (! $this->birth_date) {
            return false;
        }

        return $this->birth_date->diffInYears(Carbon::now()) >= 18;
    }
}
