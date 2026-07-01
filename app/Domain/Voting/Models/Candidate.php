<?php

declare(strict_types=1);

namespace App\Domain\Voting\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'contest_id',
        'artist_id',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'vote_count' => 'integer',
        ];
    }

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
