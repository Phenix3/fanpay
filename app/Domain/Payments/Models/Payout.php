<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'phone',
        'operator',
        'reference',
        'external_ref',
        'status',
        'approved_by',
        'approved_at',
        'processed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
