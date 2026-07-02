<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            ['name' => 'Afrobeat', 'name_en' => 'Afrobeat', 'slug' => 'afrobeat'],
            ['name' => 'Bikutsi', 'name_en' => 'Bikutsi', 'slug' => 'bikutsi'],
            ['name' => 'Bensikin', 'name_en' => 'Bensikin', 'slug' => 'bensikin'],
            ['name' => 'Makossa', 'name_en' => 'Makossa', 'slug' => 'makossa'],
            ['name' => 'Ambiance', 'name_en' => 'Ambiance', 'slug' => 'ambiance'],
            ['name' => 'Zouk', 'name_en' => 'Zouk', 'slug' => 'zouk'],
            ['name' => 'Rap / Hip-Hop', 'name_en' => 'Rap / Hip-Hop', 'slug' => 'rap-hip-hop'],
            ['name' => 'R&B', 'name_en' => 'R&B', 'slug' => 'rnb'],
            ['name' => 'Gospel', 'name_en' => 'Gospel', 'slug' => 'gospel'],
            ['name' => 'Reggae / Dancehall', 'name_en' => 'Reggae / Dancehall', 'slug' => 'reggae-dancehall'],
            ['name' => 'Jazz / Blues', 'name_en' => 'Jazz / Blues', 'slug' => 'jazz-blues'],
            ['name' => 'Musique Traditionnelle', 'name_en' => 'Traditional', 'slug' => 'traditionnelle'],
            ['name' => 'Coupé-Décalé', 'name_en' => 'Coupé-Décalé', 'slug' => 'coupe-decale'],
            ['name' => 'Soul', 'name_en' => 'Soul', 'slug' => 'soul'],
            ['name' => 'Pop', 'name_en' => 'Pop', 'slug' => 'pop'],
        ];

        foreach ($genres as $genre) {
            Genre::create($genre);
        }
    }
}
