<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemPhoto;
use App\Models\OrderItemQc;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_tracking_index(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('tracking.index'));
    }

    public function test_tracking_page_can_be_accessed_without_authentication(): void
    {
        $response = $this->get(route('tracking.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tracking/Index')
            ->has('orders')
            ->has('filters')
        );
    }

    public function test_tracking_search_by_exact_order_code_with_formatted_data(): void
    {
        $customer = Customer::create([
            'full_name' => 'Aditya Pratama',
            'phone_number' => '081299887766',
            'default_address' => 'Jl. Kaliurang KM 9, Sleman',
            'campus_cluster' => 'UGM',
        ]);

        $service = Service::create([
            'service_name' => 'Deep Clean',
            'base_price' => 35000,
            'estimated_hours' => 48,
            'is_active' => true,
        ]);

        $technician = User::create([
            'name' => 'Teknisi A',
            'email' => 'tech.a@spatoe.com',
            'password' => 'secret123',
            'role' => 'technician',
            'phone_number' => '0811111111',
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-TRACK-001',
            'customer_id' => $customer->id,
            'total_amount' => 35000,
            'discount_total' => 0,
            'final_amount' => 35000,
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS',
            'pickup_address' => 'Jl. Kaliurang KM 9, Sleman',
            'delivery_address' => 'Jl. Kaliurang KM 9, Sleman',
            'pickup_time' => now(),
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'technician_id' => $technician->id,
            'item_tag_code' => 'TAG-001',
            'shoe_brand_model' => 'Adidas Samba OG',
            'shoe_material' => 'Leather & Suede',
            'shoe_color' => 'White/Black',
            'status' => 'QC_INSPECTION',
            'estimated_completion' => now()->addHours(24),
        ]);

        OrderItemQc::create([
            'order_item_id' => $item->id,
            'inspected_by' => $technician->id,
            'is_upper_clean' => true,
            'is_midsole_clean' => true,
            'is_outsole_clean' => true,
            'is_completely_dry' => true,
            'is_perfumed' => true,
            'is_silica_packed' => true,
            'qc_passed' => true,
            'qc_notes' => 'Sepatu sudah lolos QC',
            'inspected_at' => now(),
        ]);

        OrderItemPhoto::create([
            'order_item_id' => $item->id,
            'photo_type' => 'BEFORE',
            'photo_path' => 'shoes/before_samba.jpg',
            'uploaded_by' => $technician->id,
        ]);

        OrderItemPhoto::create([
            'order_item_id' => $item->id,
            'photo_type' => 'AFTER',
            'photo_path' => 'shoes/after_samba.jpg',
            'uploaded_by' => $technician->id,
        ]);

        $response = $this->get(route('tracking.index', ['search' => 'ORD-TRACK-001']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tracking/Index')
            ->where('filters.search', 'ORD-TRACK-001')
            ->has('orders', 1)
            ->where('orders.0.order_code', 'ORD-TRACK-001')
            ->where('orders.0.customer.full_name', 'Aditya Pratama')
            ->where('orders.0.items.0.shoe_brand_model', 'Adidas Samba OG')
            ->where('orders.0.items.0.is_qc_passed', true)
            ->where('orders.0.items.0.qc.qc_passed', true)
            ->has('orders.0.items.0.photos_before', 1)
            ->has('orders.0.items.0.photos_after', 1)
            ->where('orders.0.items.0.photos_before.0.photo_type', 'BEFORE')
            ->where('orders.0.items.0.photos_after.0.photo_type', 'AFTER')
            ->where('orders.0.is_qc_passed', true)
        );
    }

    public function test_tracking_search_by_customer_phone_number(): void
    {
        $customer = Customer::create([
            'full_name' => 'Siti Nurhaliza',
            'phone_number' => '085712345678',
            'default_address' => 'Kost Putri Asri, Pogung Baru',
            'campus_cluster' => 'UGM',
        ]);

        $service = Service::create([
            'service_name' => 'Fast Clean',
            'base_price' => 25000,
            'estimated_hours' => 24,
            'is_active' => true,
        ]);

        Order::create([
            'order_code' => 'ORD-PHONE-001',
            'customer_id' => $customer->id,
            'total_amount' => 25000,
            'discount_total' => 0,
            'final_amount' => 25000,
            'payment_status' => 'UNPAID',
            'payment_method' => 'COD',
            'pickup_address' => 'Kost Putri Asri, Pogung Baru',
            'delivery_address' => 'Kost Putri Asri, Pogung Baru',
        ]);

        // Pencarian dengan format ada strip dan spasi
        $response = $this->get(route('tracking.index', ['search' => '0857-1234 5678']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tracking/Index')
            ->where('filters.search', '0857-1234 5678')
            ->has('orders', 1)
            ->where('orders.0.order_code', 'ORD-PHONE-001')
            ->where('orders.0.customer.phone_number', '085712345678')
        );
    }

    public function test_protected_routes_redirect_unauthenticated_users(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }
}
