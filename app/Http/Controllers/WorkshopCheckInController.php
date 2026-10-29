<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkshopCheckInRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemPhoto;
use App\Models\OrderItemStatusLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopCheckInController extends Controller
{
    /**
     * Display the intake & check-in page.
     */
    public function create(): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->select(['id', 'service_name', 'base_price', 'estimated_hours', 'description'])
            ->get();

        $maxTag = OrderItem::query()
            ->where('item_tag_code', 'like', 'TAG-%')
            ->get(['item_tag_code'])
            ->map(function (OrderItem $item) {
                if (preg_match('/TAG-(\d+)/i', $item->item_tag_code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        $suggestedTag = sprintf('TAG-%03d', (int) $maxTag + 1);

        return Inertia::render('Workshop/CheckIn', [
            'services' => $services,
            'suggestedTag' => $suggestedTag,
        ]);
    }

    /**
     * Lookup existing customer by phone number.
     */
    public function findCustomer(Request $request): JsonResponse
    {
        $rawPhone = (string) $request->input('phone', '');
        $cleanedPhone = preg_replace('/[^0-9]/', '', $rawPhone);

        if ($cleanedPhone === '') {
            return response()->json(['customer' => null]);
        }

        $customer = Customer::query()
            ->where('phone_number', $cleanedPhone)
            ->orWhere('phone_number', $rawPhone)
            ->first();

        return response()->json(['customer' => $customer]);
    }

    /**
     * Store incoming shoes and issue transaction invoice.
     */
    public function store(WorkshopCheckInRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $order = DB::transaction(function () use ($validated, $user, $request) {
            // 1. Simpan atau perbarui data pelanggan
            $cleanedPhone = preg_replace('/[^0-9]/', '', $validated['phone_number']);

            $customer = Customer::updateOrCreate(
                ['phone_number' => $cleanedPhone],
                [
                    'full_name' => $validated['full_name'],
                    'default_address' => $validated['default_address'],
                    'campus_cluster' => $validated['campus_cluster'] ?? null,
                ]
            );

            // 2. Generate kode nota unik (SPT-YYYYMM-XXX)
            $prefix = 'SPT-'.now()->format('Ym').'-';
            $currentMaxOrder = Order::query()
                ->lockForUpdate()
                ->where('order_code', 'like', $prefix.'%')
                ->get(['order_code'])
                ->map(function (Order $o) use ($prefix) {
                    $suffix = substr($o->order_code, strlen($prefix));

                    return is_numeric($suffix) ? (int) $suffix : 0;
                })
                ->max() ?? 0;

            $nextOrderNum = (int) $currentMaxOrder + 1;
            $orderCode = $prefix.sprintf('%03d', $nextOrderNum);

            // 3. Hitung total biaya berdasarkan relasi base_price layanan
            $itemRows = $validated['items'];
            $serviceIds = collect($itemRows)->pluck('service_id')->unique();
            $services = Service::query()->whereIn('id', $serviceIds)->get()->keyBy('id');

            $totalAmount = 0;
            foreach ($itemRows as $row) {
                $service = $services->get($row['service_id']);
                $totalAmount += $service ? (float) $service->base_price : 0;
            }

            $discountTotal = 0;
            $finalAmount = max(0, $totalAmount - $discountTotal);

            // Buat record Order
            $order = Order::create([
                'order_code' => $orderCode,
                'customer_id' => $customer->id,
                'total_amount' => $totalAmount,
                'discount_total' => $discountTotal,
                'final_amount' => $finalAmount,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'],
                'pickup_address' => $customer->default_address,
                'delivery_address' => $customer->default_address,
                'pickup_time' => now(),
            ]);

            // 4. Hitung counter tag berurutan dengan aman
            $currentMaxTag = OrderItem::query()
                ->lockForUpdate()
                ->where('item_tag_code', 'like', 'TAG-%')
                ->get(['item_tag_code'])
                ->map(function (OrderItem $item) {
                    if (preg_match('/TAG-(\d+)/i', $item->item_tag_code, $matches)) {
                        return (int) $matches[1];
                    }

                    return 0;
                })
                ->max() ?? 0;

            $tagCounter = (int) $currentMaxTag;
            $defaultTechnicianId = $user?->role === 'technician'
                ? $user->id
                : (User::where('role', 'technician')->first()?->id ?? $user?->id);

            // 5. Loop penyimpanan setiap item sepatu
            foreach ($itemRows as $index => $itemData) {
                $tagCounter++;
                $tagCode = sprintf('TAG-%03d', $tagCounter);

                $service = $services->get($itemData['service_id']);
                $estimatedHours = $service ? (int) $service->estimated_hours : 24;
                $estimatedCompletion = now()->addHours($estimatedHours);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $service?->id,
                    'technician_id' => $defaultTechnicianId,
                    'item_tag_code' => $tagCode,
                    'shoe_brand_model' => $itemData['shoe_brand_model'],
                    'shoe_material' => $itemData['shoe_material'],
                    'shoe_color' => $itemData['shoe_color'] ?? null,
                    'defect_notes' => $itemData['defect_notes'] ?? null,
                    'storage_shelf_number' => $itemData['storage_shelf_number'] ?? null,
                    'status' => 'CHECKED_IN',
                    'estimated_completion' => $estimatedCompletion,
                ]);

                // Upload foto Before jika ada
                $photo = $request->file("items.{$index}.before_photo") ?? ($itemData['before_photo'] ?? null);

                if ($photo instanceof UploadedFile && $photo->isValid()) {
                    $extension = $photo->getClientOriginalExtension() ?: ($photo->guessExtension() ?: 'webp');
                    $fileName = Str::random(40).'.'.$extension;
                    $path = 'shoes/before/'.$fileName;

                    $filePath = ($photo->getRealPath() && file_exists($photo->getRealPath()))
                        ? $photo->getRealPath()
                        : $photo->getPathname();

                    $fileContent = ($filePath && file_exists($filePath))
                        ? file_get_contents($filePath)
                        : $photo->getContent();

                    Storage::disk('public')->put($path, $fileContent);

                    OrderItemPhoto::create([
                        'order_item_id' => $orderItem->id,
                        'photo_type' => 'BEFORE',
                        'photo_path' => $path,
                        'uploaded_by' => auth()->id() ?? $user?->id,
                    ]);
                }

                // Log riwayat status awal
                OrderItemStatusLog::create([
                    'order_item_id' => $orderItem->id,
                    'previous_status' => null,
                    'new_status' => 'CHECKED_IN',
                    'notes' => 'Sepatu di-check-in di meja kasir',
                    'changed_by' => $user?->id,
                ]);
            }

            return $order;
        });

        $itemCount = count($validated['items']);

        return redirect()->route('workshop.index')->with(
            'success',
            "Nota {$order->order_code} berhasil diterbitkan dengan {$itemCount} pasang sepatu."
        );
    }
}
