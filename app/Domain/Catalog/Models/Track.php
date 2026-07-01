<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Track extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'album_id',
        'artist_id',
        'title',
        'duration',
        'file_path',
        'preview_path',
        'price',
        'is_free',
        'is_vip_exclusive',
        'play_count',
        'track_number',
        'lyrics',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'price' => 'integer',
            'is_free' => 'boolean',
            'is_vip_exclusive' => 'boolean',
            'play_count' => 'integer',
            'track_number' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
