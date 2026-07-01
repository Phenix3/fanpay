<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentRight extends Model
{
    protected $fillable = [
        'artist_id',
        'rightable_type',
        'rightable_id',
        'declaration_accepted_at',
        'socam_member',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'declaration_accepted_at' => 'datetime',
            'socam_member' => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function rightable(): MorphTo
    {
        return $this->morphTo();
    }
}
