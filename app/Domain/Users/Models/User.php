<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Content\Models\ContentRight;
use App\Domain\Users\Enums\UserRole;
use App\Domain\Vip\Models\VipSubscription;
use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\Models\Wallet;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property UserRole $role
 * @property string $locale
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(UserFactory::class)]
#[Fillable(['name', 'email', 'phone', 'password', 'role', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isArtist(): bool
    {
        return $this->role === UserRole::Artist;
    }

    public function isFan(): bool
    {
        return $this->role === UserRole::Fan;
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasVipAccess(): bool
    {
        return $this->vipSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function canRequestPayout(): bool
    {
        return $this->isArtist()
            && $this->kycVerifications()
                ->where('status', 'approved')
                ->exists();
    }

    /**
     * @return HasOne<ArtistProfile>
     */
    public function artistProfile(): HasOne
    {
        return $this->hasOne(ArtistProfile::class);
    }

    /**
     * @return HasMany<KycVerification>
     */
    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    /**
     * @return HasMany<VipSubscription>
     */
    public function vipSubscriptions(): HasMany
    {
        return $this->hasMany(VipSubscription::class);
    }

    /**
     * @return HasOne<Wallet>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany<Transaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<ContentRight>
     */
    public function contentRights(): HasMany
    {
        return $this->hasMany(ContentRight::class, 'artist_id');
    }
}
