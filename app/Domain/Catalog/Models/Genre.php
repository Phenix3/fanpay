<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Users\Models\ArtistProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'slug',
    ];

    public function artistProfiles(): HasMany
    {
        return $this->hasMany(ArtistProfile::class);
    }
}
