<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'artist_id',
        'title',
        'thumbnail',
        'file_path',
        'preview_path',
        'price',
        'is_free',
        'is_vip_exclusive',
        'view_count',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_free' => 'boolean',
            'is_vip_exclusive' => 'boolean',
            'view_count' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
