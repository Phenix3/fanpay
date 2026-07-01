<?php

declare(strict_types=1);

namespace App\Domain\Voting\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = [
        'candidate_id',
        'user_id',
        'quantity',
        'amount',
        'ip_address',
        'payment_ref',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
