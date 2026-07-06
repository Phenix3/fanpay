<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Album extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'artist_id',
        'title',
        'slug',
        'cover',
        'type',
        'price',
        'release_date',
        'genre_id',
        'is_published',
        'is_vip_exclusive',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'release_date' => 'date',
            'is_published' => 'boolean',
            'is_vip_exclusive' => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('public');
    }
}
