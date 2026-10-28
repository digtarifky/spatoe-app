<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\OrderItem;
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

        // 5. Sample Workshop Items untuk Pengujian Kanban Board
        $technician = User::where('role', 'technician')->first();
        $fastClean = Service::where('service_name', 'Fast Clean')->first();
        $deepClean = Service::where('service_name', 'Deep Clean')->first();
        $unyellowing = Service::where('service_name', 'Unyellowing')->first();

        $customer = Customer::firstOrCreate(
            ['phone_number' => '081234567890'],
            [
                'full_name' => 'Dimas Arya',
                'default_address' => 'Kost Podomoro Pogung Dalem No. 12, Sleman',
                'campus_cluster' => 'UGM',
            ]
        );

        $order = Order::firstOrCreate(
            ['order_code' => 'SPT-202610-001'],
            [
                'customer_id' => $customer->id,
                'total_amount' => 170000,
                'discount_total' => 0,
                'final_amount' => 170000,
                'payment_status' => 'PAID',
                'payment_method' => 'QRIS',
                'pickup_address' => 'Kost Podomoro Pogung Dalem No. 12, Sleman',
                'delivery_address' => 'Kost Podomoro Pogung Dalem No. 12, Sleman',
                'pickup_time' => now()->subHours(8),
            ]
        );

        $demoItems = [
            [
                'item_tag_code' => 'TAG-001',
                'shoe_brand_model' => 'Nike Dunk Low Panda',
                'shoe_material' => 'Leather',
                'shoe_color' => 'White/Black',
                'service_id' => $deepClean?->id,
                'status' => 'CHECKED_IN',
                'storage_shelf_number' => 'A-01',
                'defect_notes' => 'Terdapat goresan halus di sisi luar toe-box kanan',
                'estimated_completion' => now()->addHours(18),
            ],
            [
                'item_tag_code' => 'TAG-002',
                'shoe_brand_model' => 'Adidas Samba OG White',
                'shoe_material' => 'Leather & Suede',
                'shoe_color' => 'White/Black/Gum',
                'service_id' => $deepClean?->id,
                'status' => 'WASHING',
                'storage_shelf_number' => 'A-04',
                'defect_notes' => null,
                'estimated_completion' => now()->addHours(5), // Urgent SLA
            ],
            [
                'item_tag_code' => 'TAG-003',
                'shoe_brand_model' => 'New Balance 550 Sea Salt',
                'shoe_material' => 'Suede & Mesh',
                'shoe_color' => 'Grey/White',
                'service_id' => $fastClean?->id,
                'status' => 'DRYING',
                'storage_shelf_number' => 'B-02',
                'defect_notes' => 'Noda kopi bandel di lidah sepatu',
                'estimated_completion' => now()->subHours(2), // Overdue SLA
            ],
            [
                'item_tag_code' => 'TAG-004',
                'shoe_brand_model' => 'Air Jordan 1 Retro High',
                'shoe_material' => 'Leather',
                'shoe_color' => 'Chicago Red',
                'service_id' => $unyellowing?->id,
                'status' => 'QC_INSPECTION',
                'storage_shelf_number' => 'C-01',
                'defect_notes' => null,
                'estimated_completion' => now()->addHours(12),
            ],
            [
                'item_tag_code' => 'TAG-005',
                'shoe_brand_model' => 'Vans Old Skool Classic',
                'shoe_material' => 'Canvas & Suede',
                'shoe_color' => 'Black/White',
                'service_id' => $fastClean?->id,
                'status' => 'READY_DELIVERY',
                'storage_shelf_number' => 'D-03',
                'defect_notes' => null,
                'estimated_completion' => now()->subHours(5),
            ],
        ];

        foreach ($demoItems as $data) {
            OrderItem::updateOrCreate(
                ['item_tag_code' => $data['item_tag_code']],
                array_merge($data, [
                    'order_id' => $order->id,
                    'technician_id' => $technician?->id,
                ])
            );
        }
    }
}
