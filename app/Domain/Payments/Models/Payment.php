<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'gateway',
        'amount',
        'fees',
        'phone',
        'operator',
        'reference',
        'external_ref',
        'status',
        'webhook_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fees' => 'integer',
            'webhook_payload' => 'json',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
