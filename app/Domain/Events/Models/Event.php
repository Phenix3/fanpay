<?php

declare(strict_types=1);

namespace App\Domain\Events\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'artist_id',
        'title',
        'slug',
        'venue',
        'address',
        'date',
        'description',
        'cover',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }
}
