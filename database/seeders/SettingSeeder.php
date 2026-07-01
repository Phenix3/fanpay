<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Various\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'commission_rate' => [
                'value' => '10',
                'description' => 'Commission plateforme (%)',
            ],
            'min_payout_amount' => [
                'value' => '5000',
                'description' => 'Seuil minimum retrait (FCFA)',
            ],
            'auto_payout_threshold' => [
                'value' => '50000',
                'description' => 'Seuil retrait auto (sans validation admin)',
            ],
            'momo_fee_rate' => [
                'value' => '2.5',
                'description' => 'Frais agrégateur (%)',
            ],
            'otp_expiry_minutes' => [
                'value' => '5',
                'description' => 'Durée validité OTP',
            ],
            'max_payout_amount' => [
                'value' => '500000',
                'description' => 'Montant max par retrait',
            ],
            'vip_reminder_days' => [
                'value' => '3',
                'description' => 'Jours avant expiration pour rappel',
            ],
        ];

        foreach ($settings as $key => $data) {
            Setting::set($key, $data['value'], $data['description']);
        }
    }
}
