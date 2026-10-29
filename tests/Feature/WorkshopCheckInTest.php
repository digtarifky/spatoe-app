<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopCheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    private Service $fastClean;

    private Service $deepClean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technician = User::factory()->create([
            'name' => 'Teknisi Kasir',
            'email' => 'tech@spatoe.com',
            'role' => 'technician',
            'is_active' => true,
        ]);

        $this->fastClean = Service::create([
            'service_name' => 'Fast Clean',
            'base_price' => 25000,
            'estimated_hours' => 24,
            'description' => 'Cuci cepat upper midsole',
            'is_active' => true,
        ]);

        $this->deepClean = Service::create([
            'service_name' => 'Deep Clean',
            'base_price' => 35000,
            'estimated_hours' => 48,
            'description' => 'Cuci menyeluruh luar dalam',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_checkin_page(): void
    {
        $response = $this->get(route('workshop.checkin'));
        $response->assertRedirect(route('login'));

        $storeResponse = $this->post(route('workshop.checkin.store'), []);
        $storeResponse->assertRedirect(route('login'));

        $findResponse = $this->get(route('workshop.checkin.find-customer', ['phone' => '081234567890']));
        $findResponse->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_checkin_page_with_services_and_suggested_tag(): void
    {
        // Jika belum ada item, tag pertama adalah TAG-001
        $response = $this->actingAs($this->technician)->get(route('workshop.checkin'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workshop/CheckIn')
            ->has('services', 2)
            ->where('suggestedTag', 'TAG-001')
        );

        // Jika sudah ada item sampai TAG-005, tag berikutnya adalah TAG-006
        $customer = Customer::create([
            'full_name' => 'Testing Tag',
            'phone_number' => '0811111111',
            'default_address' => 'Jl. Test 1',
        ]);

        $order = Order::create([
            'order_code' => 'SPT-202610-999',
            'customer_id' => $customer->id,
            'total_amount' => 25000,
            'discount_total' => 0,
            'final_amount' => 25000,
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS',
            'pickup_address' => 'Jl. Test 1',
            'delivery_address' => 'Jl. Test 1',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $this->fastClean->id,
            'item_tag_code' => 'TAG-005',
            'shoe_brand_model' => 'Nike Air Force 1',
            'shoe_material' => 'Leather',
            'status' => 'CHECKED_IN',
        ]);

        $responseWithExisting = $this->actingAs($this->technician)->get(route('workshop.checkin'));
        $responseWithExisting->assertInertia(fn (Assert $page) => $page
            ->where('suggestedTag', 'TAG-006')
        );
    }

    public function test_find_customer_returns_customer_by_phone_number(): void
    {
        $customer = Customer::create([
            'full_name' => 'Rian Pratama',
            'phone_number' => '081298765432',
            'default_address' => 'Kost Griya Amarta No. 4, Surabaya',
            'campus_cluster' => 'UPN',
        ]);

        // Cari dengan nomor bersih
        $response = $this->actingAs($this->technician)->getJson(
            route('workshop.checkin.find-customer', ['phone' => '081298765432'])
        );

        $response->assertOk();
        $response->assertJson([
            'customer' => [
                'id' => $customer->id,
                'full_name' => 'Rian Pratama',
                'phone_number' => '081298765432',
                'campus_cluster' => 'UPN',
            ],
        ]);

        // Cari dengan format strip & spasi
        $responseFormatted = $this->actingAs($this->technician)->getJson(
            route('workshop.checkin.find-customer', ['phone' => '0812-9876-5432'])
        );

        $responseFormatted->assertOk();
        $responseFormatted->assertJson([
            'customer' => [
                'id' => $customer->id,
                'full_name' => 'Rian Pratama',
            ],
        ]);
    }

    public function test_find_customer_returns_null_when_not_found(): void
    {
        $response = $this->actingAs($this->technician)->getJson(
            route('workshop.checkin.find-customer', ['phone' => '089999999999'])
        );

        $response->assertOk();
        $response->assertJson([
            'customer' => null,
        ]);
    }

    public function test_checkin_validates_required_fields(): void
    {
        $response = $this->actingAs($this->technician)->post(route('workshop.checkin.store'), []);

        $response->assertSessionHasErrors([
            'phone_number',
            'full_name',
            'default_address',
            'payment_status',
            'payment_method',
            'items',
        ]);
    }

    public function test_checkin_successfully_stores_customer_order_items_and_redirects(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('before_shoe.jpg', 600, 600);

        $payload = [
            'phone_number' => '085712349988',
            'full_name' => 'Nabila Putri',
            'default_address' => 'Kost Melati Sukolilo',
            'campus_cluster' => 'ITS',
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS',
            'items' => [
                [
                    'shoe_brand_model' => 'Adidas Samba OG',
                    'shoe_material' => 'Leather',
                    'shoe_color' => 'White/Black',
                    'service_id' => $this->deepClean->id,
                    'storage_shelf_number' => 'A-01',
                    'defect_notes' => 'Ada noda kecil di toe box',
                    'before_photo' => $photo,
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->post(route('workshop.checkin.store'), $payload);

        $response->assertRedirect(route('workshop.index'));
        $response->assertSessionHas('success');

        // Customer tersimpan
        $this->assertDatabaseHas('customers', [
            'phone_number' => '085712349988',
            'full_name' => 'Nabila Putri',
            'campus_cluster' => 'ITS',
        ]);

        // Order tersimpan
        $this->assertDatabaseHas('orders', [
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS',
            'total_amount' => 35000,
            'final_amount' => 35000,
        ]);

        $order = Order::latest()->first();
        $this->assertStringStartsWith('SPT-'.now()->format('Ym').'-', $order->order_code);

        // Order item tersimpan
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'shoe_brand_model' => 'Adidas Samba OG',
            'item_tag_code' => 'TAG-001',
            'status' => 'CHECKED_IN',
            'storage_shelf_number' => 'A-01',
        ]);

        $item = OrderItem::latest()->first();

        // Foto tersimpan
        $this->assertDatabaseHas('order_item_photos', [
            'order_item_id' => $item->id,
            'photo_type' => 'BEFORE',
            'uploaded_by' => $this->technician->id,
        ]);

        // Log status awal tersimpan
        $this->assertDatabaseHas('order_item_status_logs', [
            'order_item_id' => $item->id,
            'new_status' => 'CHECKED_IN',
            'changed_by' => $this->technician->id,
        ]);
    }

    public function test_checkin_handles_multi_item_order_with_sequential_tags(): void
    {
        $payload = [
            'phone_number' => '081200001111',
            'full_name' => 'Pelanggan Borongan',
            'default_address' => 'Jl. Gebang Wetan No. 10',
            'campus_cluster' => 'ITS',
            'payment_status' => 'PAID',
            'payment_method' => 'COD',
            'items' => [
                [
                    'shoe_brand_model' => 'Sepatu 1 - Converse Chuck',
                    'shoe_material' => 'Canvas',
                    'shoe_color' => 'Black',
                    'service_id' => $this->fastClean->id, // 25000
                    'storage_shelf_number' => 'B-01',
                ],
                [
                    'shoe_brand_model' => 'Sepatu 2 - Nike Dunk Low',
                    'shoe_material' => 'Leather',
                    'shoe_color' => 'Panda',
                    'service_id' => $this->deepClean->id, // 35000
                    'storage_shelf_number' => 'B-02',
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->post(route('workshop.checkin.store'), $payload);

        $response->assertRedirect(route('workshop.index'));

        $order = Order::latest()->first();
        // Total 25000 + 35000 = 60000
        $this->assertEquals(60000, (float) $order->total_amount);
        $this->assertEquals(60000, (float) $order->final_amount);
        $this->assertCount(2, $order->items);

        $tags = $order->items->pluck('item_tag_code')->all();
        $this->assertEquals(['TAG-001', 'TAG-002'], $tags);
    }
}

