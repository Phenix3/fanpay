<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Catalog
            'upload-catalog', 'edit-catalog', 'delete-catalog', 'publish-catalog',
            // Events
            'create-event', 'edit-event', 'delete-event', 'sell-tickets',
            // Wallet
            'request-payout', 'view-wallet', 'tip-artist',
            // VIP
            'create-vip-plan', 'subscribe-vip',
            // Dedications
            'request-dedication', 'respond-dedication',
            // Voting
            'vote', 'create-contest',
            // Store
            'create-product', 'purchase-product',
            // Admin
            'approve-kyc', 'approve-payout', 'manage-disputes', 'manage-settings', 'manage-users', 'view-analytics',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // ─── Rôles ───────────────────────────────────────────

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $artist = Role::firstOrCreate(['name' => 'artist', 'guard_name' => 'web']);
        $artist->syncPermissions([
            'upload-catalog', 'edit-catalog', 'delete-catalog', 'publish-catalog',
            'create-event', 'edit-event', 'delete-event', 'sell-tickets',
            'request-payout', 'view-wallet',
            'create-vip-plan', 'respond-dedication',
            'vote', 'create-product', 'tip-artist', 'subscribe-vip',
        ]);

        $fan = Role::firstOrCreate(['name' => 'fan', 'guard_name' => 'web']);
        $fan->syncPermissions([
            'vote', 'tip-artist', 'subscribe-vip', 'purchase-product', 'request-dedication',
        ]);
    }
}
