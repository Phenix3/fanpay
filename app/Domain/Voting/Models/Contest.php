<?php

declare(strict_types=1);

namespace App\Domain\Voting\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contest extends Model
{
    protected $fillable = [
        'title',
        'description',
        'vote_price',
        'cover',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vote_price' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }
}
