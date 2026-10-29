<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Service $service;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'technician',
        ]);

        $this->customer = Customer::create([
            'full_name' => 'Budi Santoso',
            'phone_number' => '081234567890',
            'default_address' => 'Jl. Kaliurang KM 5',
        ]);

        $this->service = Service::create([
            'service_name' => 'Deep Clean',
            'base_price' => 35000,
            'estimated_hours' => 48,
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_code' => 'ORD-WS-001',
            'customer_id' => $this->customer->id,
            'total_amount' => 35000,
            'discount_total' => 0,
            'final_amount' => 35000,
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS',
            'pickup_address' => 'Jl. Kaliurang KM 5',
            'delivery_address' => 'Jl. Kaliurang KM 5',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_workshop(): void
    {
        $response = $this->get(route('workshop.index'));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_workshop(): void
    {
        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'technician_id' => $this->user->id,
            'item_tag_code' => 'TAG-WS-001',
            'shoe_brand_model' => 'Nike Air Force 1',
            'shoe_material' => 'Leather',
            'status' => 'CHECKED_IN',
            'estimated_completion' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->user)->get(route('workshop.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workshop/Index')
            ->has('items', 1)
            ->has('grouped_items.CHECKED_IN', 1)
            ->has('grouped_items.WASHING', 0)
            ->has('grouped_items.DRYING', 0)
            ->has('grouped_items.QC_INSPECTION', 0)
            ->has('grouped_items.READY_DELIVERY', 0)
            ->has('metrics')
            ->where('metrics.total_active', 1)
            ->where('metrics.needs_washing', 1)
            ->has('filters')
        );
    }

    public function test_workshop_ignores_delivered_and_cancelled_items(): void
    {
        // Item aktif
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-ACTIVE-01',
            'shoe_brand_model' => 'Adidas Samba',
            'shoe_material' => 'Leather',
            'status' => 'WASHING',
        ]);

        // Item delivered
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-DELIVERED-01',
            'shoe_brand_model' => 'Vans Old Skool',
            'shoe_material' => 'Canvas',
            'status' => 'DELIVERED',
        ]);

        // Item cancelled
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-CANCELLED-01',
            'shoe_brand_model' => 'Converse Chuck 70',
            'shoe_material' => 'Canvas',
            'status' => 'CANCELLED',
        ]);

        $response = $this->actingAs($this->user)->get(route('workshop.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workshop/Index')
            ->has('items', 1)
            ->where('items.0.item_tag_code', 'TAG-ACTIVE-01')
        );
    }

    public function test_workshop_search_by_item_tag_code_or_brand_model(): void
    {
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-JORDAN-99',
            'shoe_brand_model' => 'Air Jordan 1 High Lost and Found',
            'shoe_material' => 'Leather',
            'status' => 'CHECKED_IN',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-NB-550',
            'shoe_brand_model' => 'New Balance 550 White Grey',
            'shoe_material' => 'Leather',
            'status' => 'WASHING',
        ]);

        // Search by tag
        $response = $this->actingAs($this->user)->get(route('workshop.index', ['search' => 'JORDAN-99']));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.item_tag_code', 'TAG-JORDAN-99')
        );

        // Search by shoe model
        $response2 = $this->actingAs($this->user)->get(route('workshop.index', ['search' => 'Balance 550']));
        $response2->assertOk();
        $response2->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.item_tag_code', 'TAG-NB-550')
        );
    }

    public function test_update_status_updates_item_and_records_status_log(): void
    {
        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'technician_id' => $this->user->id,
            'item_tag_code' => 'TAG-UPDATE-001',
            'shoe_brand_model' => 'Puma Suede',
            'shoe_material' => 'Suede',
            'status' => 'CHECKED_IN',
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('workshop.items.update-status', $item),
            [
                'new_status' => 'WASHING',
                'notes' => 'Sepatu mulai dicuci oleh teknisi',
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $item->refresh();
        $this->assertEquals('WASHING', $item->status);

        $this->assertDatabaseHas('order_item_status_logs', [
            'order_item_id' => $item->id,
            'previous_status' => 'CHECKED_IN',
            'new_status' => 'WASHING',
            'notes' => 'Sepatu mulai dicuci oleh teknisi',
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_update_status_sets_completed_at_when_status_is_ready_delivery(): void
    {
        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-READY-001',
            'shoe_brand_model' => 'Salomon XT-6',
            'shoe_material' => 'Mesh',
            'status' => 'QC_INSPECTION',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('workshop.items.update-status', $item),
            [
                'new_status' => 'READY_DELIVERY',
            ]
        );

        $response->assertSessionHasNoErrors();
        $item->refresh();

        $this->assertEquals('READY_DELIVERY', $item->status);
        $this->assertNotNull($item->completed_at);
    }

    public function test_update_status_validates_new_status(): void
    {
        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'item_tag_code' => 'TAG-INVALID-001',
            'shoe_brand_model' => 'Adidas Gazelle',
            'shoe_material' => 'Suede',
            'status' => 'CHECKED_IN',
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('workshop.items.update-status', $item),
            [
                'new_status' => 'INVALID_STATUS_NAME',
            ]
        );

        $response->assertSessionHasErrors(['new_status']);
    }

    public function test_update_status_accepts_status_field_and_assigns_technician_id(): void
    {
        $technician = User::factory()->create(['role' => 'technician', 'name' => 'Teknisi Budi']);

        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'technician_id' => null,
            'item_tag_code' => 'TAG-STATION-01',
            'shoe_brand_model' => 'Nike Dunk Low',
            'shoe_material' => 'Leather',
            'status' => 'WASHING',
        ]);

        $response = $this->actingAs($technician)->patch(
            route('workshop.items.update-status', $item),
            [
                'status' => 'DRYING',
                'notes' => 'Pindah ke Pos Dryer',
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $item->refresh();
        $this->assertEquals('DRYING', $item->status);
        $this->assertEquals($technician->id, $item->technician_id);

        $this->assertDatabaseHas('order_item_status_logs', [
            'order_item_id' => $item->id,
            'previous_status' => 'WASHING',
            'new_status' => 'DRYING',
            'notes' => 'Pindah ke Pos Dryer',
            'changed_by' => $technician->id,
        ]);
    }

    public function test_update_status_completes_order_item_to_delivered(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);

        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'technician_id' => $technician->id,
            'item_tag_code' => 'TAG-DELIVER-01',
            'shoe_brand_model' => 'Asics Gel Kayano',
            'shoe_material' => 'Mesh',
            'status' => 'READY_DELIVERY',
        ]);

        $response = $this->actingAs($technician)->patch(
            route('workshop.items.update-status', $item),
            [
                'status' => 'DELIVERED',
            ]
        );

        $response->assertSessionHasNoErrors();
        $item->refresh();

        $this->assertEquals('DELIVERED', $item->status);
        $this->assertNotNull($item->completed_at);
        $this->assertDatabaseHas('order_item_status_logs', [
            'order_item_id' => $item->id,
            'previous_status' => 'READY_DELIVERY',
            'new_status' => 'DELIVERED',
            'changed_by' => $technician->id,
        ]);
    }

    public function test_update_status_blocks_illegal_status_jump(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);

        $item = OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'technician_id' => $technician->id,
            'item_tag_code' => 'TAG-JUMP-01',
            'shoe_brand_model' => 'Nike Pegasus',
            'shoe_material' => 'Mesh',
            'status' => 'CHECKED_IN',
        ]);

        // Coba loncat langsung dari CHECKED_IN ke DELIVERED
        $response = $this->actingAs($technician)->patch(
            route('workshop.items.update-status', $item),
            [
                'status' => 'DELIVERED',
            ]
        );

        $response->assertSessionHasErrors(['status']);

        $item->refresh();
        $this->assertEquals('CHECKED_IN', $item->status);
    }
}
