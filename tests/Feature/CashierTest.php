<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'full_name' => 'Budi Santoso',
            'phone_number' => '081234567890',
            'default_address' => 'Jl. Kaliurang KM 5, Surabaya',
        ]);

        $this->service = Service::create([
            'service_name' => 'Fast Clean',
            'base_price' => 25000,
            'estimated_hours' => 24,
            'is_active' => true,
        ]);
    }

    private function createOrder(string $code = 'SP-TEST-001', string $paymentStatus = 'UNPAID'): Order
    {
        return Order::create([
            'order_code' => $code,
            'customer_id' => $this->customer->id,
            'total_amount' => 25000,
            'discount_total' => 0,
            'final_amount' => 25000,
            'payment_status' => $paymentStatus,
            'payment_method' => 'QRIS',
            'pickup_address' => 'Jl. Kaliurang KM 5, Surabaya',
            'delivery_address' => 'Jl. Kaliurang KM 5, Surabaya',
        ]);
    }

    private function createOrderItem(Order $order, string $tag = 'TAG-TEST-001', string $status = 'READY_DELIVERY'): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => $tag,
            'shoe_brand_model' => 'Nike Dunk Low',
            'shoe_material' => 'Leather',
            'shoe_color' => 'White/Black',
            'status' => $status,
        ]);
    }

    public function test_cashier_user_redirects_to_cashier_dashboard_upon_login(): void
    {
        $cashier = User::factory()->create([
            'email' => 'kasir@spatoe.com',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
        ]);

        $response = $this->post('/login', [
            'email' => 'kasir@spatoe.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('cashier.index', absolute: false));
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_technician_user_redirects_to_workshop_dashboard_upon_login(): void
    {
        $technician = User::factory()->create([
            'email' => 'teknisi@spatoe.com',
            'password' => bcrypt('password123'),
            'role' => 'technician',
        ]);

        $response = $this->post('/login', [
            'email' => 'teknisi@spatoe.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('workshop.index', absolute: false));
        $this->assertAuthenticatedAs($technician);
    }

    public function test_cashier_dashboard_can_be_rendered(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = $this->createOrder('SP-TEST-001', 'UNPAID');
        $this->createOrderItem($order, 'TAG-TEST-001', 'READY_DELIVERY');

        $response = $this->actingAs($cashier)->get(route('cashier.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Cashier/Index')
            ->has('items')
            ->has('metrics')
            ->where('metrics.ready_pickup', 1)
        );
    }

    public function test_cashier_can_handover_ready_delivery_item_to_customer(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = $this->createOrder('SP-TEST-002', 'UNPAID');
        $item = $this->createOrderItem($order, 'TAG-TEST-002', 'READY_DELIVERY');

        $response = $this->actingAs($cashier)->post(route('cashier.items.handover', $item->id));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'status' => 'DELIVERED',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'PAID',
        ]);
        $this->assertDatabaseHas('order_item_status_logs', [
            'order_item_id' => $item->id,
            'previous_status' => 'READY_DELIVERY',
            'new_status' => 'DELIVERED',
            'changed_by' => $cashier->id,
        ]);
    }

    public function test_handover_fails_if_item_is_not_ready_delivery(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = $this->createOrder('SP-TEST-003', 'UNPAID');
        $item = $this->createOrderItem($order, 'TAG-TEST-003', 'WASHING');

        $response = $this->actingAs($cashier)->post(route('cashier.items.handover', $item->id));

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('WASHING', $item->fresh()->status);
    }
}
