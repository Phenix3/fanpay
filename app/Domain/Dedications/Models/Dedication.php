<?php

declare(strict_types=1);

namespace App\Domain\Dedications\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dedication extends Model
{
    protected $fillable = [
        'artist_id',
        'fan_id',
        'message_request',
        'price',
        'status',
        'response_type',
        'response_media',
        'response_message',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function fan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fan_id');
    }
}
