<?php

namespace App\Models;

use App\Notifications\VerifyWholesaleEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'legacy_id',
    'name',
    'legacy_username',
    'email',
    'legacy_email',
    'gender',
    'phone',
    'avatar',
    'password',
    'account_type',
    'admin_sales_channel',
    'approval_status',
    'legacy_role_id',
    'price_tier_id',
    'must_reset_password',
    'approved_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_reset_password' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class);
    }

    public function resellerProfile(): HasOne
    {
        return $this->hasOne(ResellerProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function avatarUrl(): ?string
    {
        return blank($this->avatar) ? null : \App\Support\StorefrontAsset::uploaded($this->avatar);
    }

    public function isApprovedWholesale(): bool
    {
        return $this->account_type === 'wholesale' && $this->approval_status === 'approved';
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyWholesaleEmail);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->account_type === 'admin' && $this->approval_status === 'approved';
    }
}
