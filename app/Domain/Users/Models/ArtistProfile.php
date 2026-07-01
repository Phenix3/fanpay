<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArtistProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'stage_name',
        'slug',
        'bio',
        'bio_en',
        'avatar',
        'cover',
        'genre_id',
        'is_verified',
        'social_links',
        'stats',
        'customization',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'social_links' => 'json',
            'stats' => 'json',
            'customization' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
