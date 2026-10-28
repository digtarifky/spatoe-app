<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\InventoryItem;
use App\Models\MembershipPlan;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemPhoto;
use App\Models\OrderItemQc;
use App\Models\OrderItemStatusLog;
use App\Models\OrderReview;
use App\Models\Service;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatoeDatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_default_records(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@spatoe.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'teknisi@spatoe.com',
            'role' => 'technician',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'kurir@spatoe.com',
            'role' => 'courier',
        ]);

        $this->assertDatabaseCount('services', 3);
        $this->assertDatabaseHas('services', ['service_name' => 'Fast Clean', 'base_price' => 25000]);
        $this->assertDatabaseHas('services', ['service_name' => 'Deep Clean', 'base_price' => 35000]);
        $this->assertDatabaseHas('services', ['service_name' => 'Unyellowing', 'base_price' => 50000]);

        $this->assertDatabaseCount('membership_plans', 2);
        $this->assertDatabaseHas('membership_plans', ['plan_name' => 'Solo Pass', 'quota_pairs' => 4]);
        $this->assertDatabaseHas('membership_plans', ['plan_name' => 'Roommate Pass', 'quota_pairs' => 8]);

        $this->assertDatabaseHas('vouchers', ['code' => 'SPATOEKAMPUS', 'discount_amount' => 5000]);
    }

    public function test_customer_and_membership_relationships(): void
    {
        $plan = MembershipPlan::create([
            'plan_name' => 'Solo Pass',
            'price' => 90000,
            'quota_pairs' => 4,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'full_name' => 'Budi Santoso',
            'phone_number' => '081298765432',
            'default_address' => 'Jl. Kaliurang KM 5, Sleman',
            'campus_cluster' => 'UGM',
        ]);

        $membership = CustomerMembership::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'remaining_quota' => 4,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($customer->memberships->contains($membership));
        $this->assertEquals($customer->id, $membership->customer->id);
        $this->assertEquals($plan->id, $membership->plan->id);
        $this->assertTrue($plan->customerMemberships->contains($membership));
    }

    public function test_order_and_items_lifecycle_relationships(): void
    {
        $customer = Customer::create([
            'full_name' => 'Ahmad Fauzi',
            'phone_number' => '081233445566',
            'default_address' => 'Kost Podomoro, Pogung',
            'campus_cluster' => 'UGM',
        ]);

        $courier = User::create([
            'name' => 'Kurir Cepat',
            'email' => 'courier.test@spatoe.com',
            'password' => 'secret123',
            'role' => 'courier',
            'phone_number' => '081200000001',
            'is_active' => true,
        ]);

        $technician = User::create([
            'name' => 'Teknisi Handal',
            'email' => 'tech.test@spatoe.com',
            'password' => 'secret123',
            'role' => 'technician',
            'phone_number' => '081200000002',
            'is_active' => true,
        ]);

        $voucher = Voucher::create([
            'code' => 'HEMAT5K',
            'discount_amount' => 5000,
            'min_order_pairs' => 1,
            'valid_until' => now()->addDays(14),
            'is_active' => true,
        ]);

        $service = Service::create([
            'service_name' => 'Fast Clean',
            'base_price' => 25000,
            'estimated_hours' => 24,
            'description' => 'Fast clean 24h',
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-20261028-001',
            'customer_id' => $customer->id,
            'courier_id' => $courier->id,
            'voucher_id' => $voucher->id,
            'total_amount' => 25000,
            'discount_total' => 5000,
            'final_amount' => 20000,
            'payment_status' => 'UNPAID',
            'payment_method' => 'QRIS',
            'pickup_address' => 'Kost Podomoro, Pogung',
            'delivery_address' => 'Kost Podomoro, Pogung',
            'pickup_time' => now(),
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'technician_id' => $technician->id,
            'item_tag_code' => 'TAG-001-NIKE',
            'shoe_brand_model' => 'Nike Air Jordan 1',
            'shoe_material' => 'Leather',
            'shoe_color' => 'White/Red',
            'defect_notes' => 'Sedikit goresan di toe box',
            'storage_shelf_number' => 'A-04',
            'status' => 'QC_INSPECTION',
            'estimated_completion' => now()->addHours(24),
        ]);

        // QC Inspection
        $qc = OrderItemQc::create([
            'order_item_id' => $item->id,
            'inspected_by' => $technician->id,
            'is_upper_clean' => true,
            'is_midsole_clean' => true,
            'is_outsole_clean' => true,
            'is_completely_dry' => true,
            'is_perfumed' => true,
            'is_silica_packed' => true,
            'qc_passed' => true,
            'qc_notes' => 'Sepatu wangi dan bersih optimal',
            'inspected_at' => now(),
        ]);

        // Photo
        $photo = OrderItemPhoto::create([
            'order_item_id' => $item->id,
            'photo_type' => 'BEFORE',
            'photo_path' => 'photos/before_001.jpg',
            'uploaded_by' => $technician->id,
        ]);

        // Status Log
        $log = OrderItemStatusLog::create([
            'order_item_id' => $item->id,
            'previous_status' => 'WASHING',
            'new_status' => 'QC_INSPECTION',
            'notes' => 'Pencucian selesai, masuk tahap QC',
            'changed_by' => $technician->id,
        ]);

        // Review
        $review = OrderReview::create([
            'order_id' => $order->id,
            'rating' => 5,
            'review_text' => 'Pelayanan cepat dan bersih banget!',
            'is_public' => true,
        ]);

        // Assert relationships
        $this->assertEquals($customer->id, $order->customer->id);
        $this->assertEquals($courier->id, $order->courier->id);
        $this->assertEquals($voucher->id, $order->voucher->id);
        $this->assertTrue($customer->orders->contains($order));
        $this->assertTrue($courier->handledOrders->contains($order));
        $this->assertTrue($voucher->orders->contains($order));

        $this->assertTrue($order->items->contains($item));
        $this->assertEquals($order->id, $item->order->id);
        $this->assertEquals($service->id, $item->service->id);
        $this->assertEquals($technician->id, $item->technician->id);
        $this->assertTrue($technician->handledItems->contains($item));
        $this->assertTrue($service->orderItems->contains($item));

        $this->assertEquals($item->id, $qc->orderItem->id);
        $this->assertEquals($qc->id, $item->qc->id);
        $this->assertTrue($technician->inspectedQc->contains($qc));

        $this->assertTrue($item->photos->contains($photo));
        $this->assertEquals($technician->id, $photo->uploader->id);
        $this->assertTrue($technician->uploadedPhotos->contains($photo));

        $this->assertTrue($item->statusLogs->contains($log));
        $this->assertEquals($technician->id, $log->user->id);

        $this->assertEquals($order->id, $review->order->id);
        $this->assertEquals($review->id, $order->review->id);
    }

    public function test_inventory_and_operational_expenses(): void
    {
        $admin = User::create([
            'name' => 'Admin Expense',
            'email' => 'admin.exp@spatoe.com',
            'password' => 'secret123',
            'role' => 'admin',
            'phone_number' => '081299999999',
            'is_active' => true,
        ]);

        $inventory = InventoryItem::create([
            'item_name' => 'Standard Cleaner Solution 1L',
            'stock_quantity' => 15.50,
            'unit' => 'Botol',
            'min_stock_alert' => 5.00,
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $inventory->id,
            'item_name' => 'Standard Cleaner Solution 1L',
        ]);

        $expense = OperationalExpense::create([
            'category' => 'BAHAN_BAKU',
            'description' => 'Pembelian 10 Botol Cleaner Shoe Cleaner',
            'amount' => 450000.00,
            'receipt_path' => 'receipts/nota_cleaner_01.jpg',
            'recorded_by' => $admin->id,
            'expense_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('operational_expenses', [
            'id' => $expense->id,
            'category' => 'BAHAN_BAKU',
        ]);
        $this->assertEquals($admin->id, $expense->recorder->id);
    }
}
