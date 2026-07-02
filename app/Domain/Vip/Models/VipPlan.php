<?php

declare(strict_types=1);

namespace App\Domain\Vip\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VipPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'artist_id',
        'name',
        'duration_days',
        'price',
        'perks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'integer',
            'perks' => 'json',
            'is_active' => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VipSubscription::class, 'plan_id');
    }
}
