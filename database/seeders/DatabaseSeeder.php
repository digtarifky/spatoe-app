<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use App\Models\Service;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Default Pengguna
        User::updateOrCreate(
            ['email' => 'admin@spatoe.com'],
            [
                'name' => 'Admin Spatoe',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone_number' => '081234567890',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'teknisi@spatoe.com'],
            [
                'name' => 'Teknisi Spatoe',
                'password' => Hash::make('password123'),
                'role' => 'technician',
                'phone_number' => '081234567891',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kurir@spatoe.com'],
            [
                'name' => 'Kurir Spatoe',
                'password' => Hash::make('password123'),
                'role' => 'courier',
                'phone_number' => '081234567892',
                'is_active' => true,
            ]
        );

        // 2. Master Layanan
        $services = [
            [
                'service_name' => 'Fast Clean',
                'base_price' => 25000,
                'estimated_hours' => 24,
                'description' => 'Pembersihan cepat untuk bagian upper dan midsole sepatu.',
                'is_active' => true,
            ],
            [
                'service_name' => 'Deep Clean',
                'base_price' => 35000,
                'estimated_hours' => 48,
                'description' => 'Pembersihan menyeluruh seluruh bagian luar dan dalam sepatu.',
                'is_active' => true,
            ],
            [
                'service_name' => 'Unyellowing',
                'base_price' => 50000,
                'estimated_hours' => 72,
                'description' => 'Treatment khusus untuk memulihkan sol sepatu yang menguning (oksidasi).',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['service_name' => $service['service_name']],
                $service
            );
        }

        // 3. Paket Langganan Spatoe Pass
        $plans = [
            [
                'plan_name' => 'Solo Pass',
                'price' => 90000,
                'quota_pairs' => 4,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Roommate Pass',
                'price' => 170000,
                'quota_pairs' => 8,
                'duration_days' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(
                ['plan_name' => $plan['plan_name']],
                $plan
            );
        }

        // 4. Voucer Promo
        Voucher::updateOrCreate(
            ['code' => 'SPATOEKAMPUS'],
            [
                'discount_amount' => 5000,
                'min_order_pairs' => 1,
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ]
        );
    }
}
