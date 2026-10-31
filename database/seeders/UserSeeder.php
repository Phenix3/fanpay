<?php

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::factory()
            ->create([
                "name" => "Admin FanPay",
                "email" => "admin@fanpay.cm",
                "phone" => "691234567",
                "role" => "admin",
                "locale" => "fr",
            ])
            ->assignRole("admin");

        User::factory()
            ->create([
                "name" => "Artist FanPay",
                "email" => "artist@fanpay.cm",
                "phone" => "691234568",
                "role" => "artist",
                "locale" => "fr",
            ])
            ->assignRole("artist");
    }
}
