<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'line_access_token',
        'line_refresh_token',
        'line_picture_url',
        'notification_preferences',
        'preferred_theme',
        'marketing_email_enabled',
        'marketing_line_enabled',
        'marketing_consent_at',
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
            'notification_preferences' => 'array',
            'marketing_email_enabled' => 'boolean',
            'marketing_line_enabled' => 'boolean',
            'marketing_consent_at' => 'datetime',
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

    /**
     * Check if user wants to receive marketing emails
     */
    public function wantsMarketingEmail(): bool
    {
        return $this->marketing_email_enabled ?? true;
    }

    /**
     * Check if user wants to receive marketing LINE messages
     */
    public function wantsMarketingLine(): bool
    {
        return $this->marketing_line_enabled && $this->hasLineUid();
    }

    /**
     * Get notification preference for a specific type
     */
    public function getNotificationPreference(string $type, string $channel = 'email'): bool
    {
        $preferences = $this->notification_preferences ?? [];

        return $preferences[$type][$channel] ?? true;
    }

    /**
     * Check if user can receive LINE notifications
     */
    public function canReceiveLineNotifications(): bool
    {
        return $this->hasLineUid() && ($this->marketing_line_enabled ?? true);
    }

    /**
     * Scope for users who can receive marketing notifications
     */
    public function scopeMarketingEnabled($query, string $channel = 'email')
    {
        if ($channel === 'line') {
            return $query->withLineUid()->where('marketing_line_enabled', true);
        }

        return $query->where('marketing_email_enabled', true);
    }

    /**
     * Get user's preferred theme or fall back to site default
     */
    public function getPreferredTheme(): ?string
    {
        return $this->preferred_theme;
    }

    /**
     * Set user's preferred theme
     */
    public function setPreferredTheme(?string $theme): bool
    {
        $this->preferred_theme = $theme;

        return $this->save();
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasAnyPermission($permissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
            if (! $role) {
                return;
            }
        }

        $this->roles()->detach($role->id);
    }

    /**
     * Sync roles to the user.
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($roleIds);
    }

    /**
     * Get all permissions for the user through their roles.
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
    }
}
