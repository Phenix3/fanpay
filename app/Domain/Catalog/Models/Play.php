<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Play extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'track_id',
        'user_id',
        'ip_address',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
