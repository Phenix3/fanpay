<?php

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
        ]);

        // Create default admin user
        User::factory()->create([
            'name' => 'Admin FanPay',
            'email' => 'admin@fanpay.cm',
            'phone' => '691234567',
            'role' => 'admin',
            'locale' => 'fr',
        ])->assignRole('admin');
    }
}
