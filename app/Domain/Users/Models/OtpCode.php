<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'phone',
        'code',
        'purpose',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->verified_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < $this->max_attempts;
    }
}
