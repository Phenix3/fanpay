<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RevenueSplit extends Model
{
    protected $fillable = [
        'splittable_type',
        'splittable_id',
        'artist_id',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
        ];
    }

    public function splittable(): MorphTo
    {
        return $this->morphTo();
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
