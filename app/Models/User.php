<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'phone',
        'line_uid',
        'line_display_name',
        'password',
        'role',
        'is_active',
    ];

    /**
     * Get the URL for the user's avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(UserRental::class);
    }

    public function activeRental(): ?UserRental
    {
        return $this->rentals()
            ->where('status', UserRental::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function rentalPayments(): HasMany
    {
        return $this->hasMany(RentalPayment::class);
    }

    public function rentalInvoices(): HasMany
    {
        return $this->hasMany(RentalInvoice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasActiveRental(): bool
    {
        return $this->activeRental() !== null;
    }

    public function hasLineUid(): bool
    {
        return ! empty($this->line_uid);
    }

    public function scopeWithLineUid($query)
    {
        return $query->whereNotNull('line_uid')->where('line_uid', '!=', '');
    }
}
