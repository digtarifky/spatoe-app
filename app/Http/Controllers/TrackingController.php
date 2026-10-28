<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    /**
     * Display public order tracking page.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $cleanedPhone = preg_replace('/[^0-9]/', '', $search);

        $orders = collect();

        if ($search !== '') {
            $orders = Order::query()
                ->with([
                    'customer:id,full_name,phone_number,default_address',
                    'items.service:id,service_name,base_price',
                    'items.qc:id,order_item_id,qc_passed,inspected_at',
                    'items.photos' => function ($query) {
                        $query->select(['id', 'order_item_id', 'photo_type', 'photo_path', 'created_at'])
                            ->latest();
                    },
                ])
                ->where(function ($query) use ($search, $cleanedPhone) {
                    $query->where('order_code', $search);

                    if ($cleanedPhone !== '') {
                        $query->orWhereHas('customer', function ($customerQuery) use ($cleanedPhone) {
                            $customerQuery->where('phone_number', 'like', "%{$cleanedPhone}%");
                        });
                    }
                })
                ->latest()
                ->take(5)
                ->get();
        }

        $formattedOrders = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'total_amount' => (float) $order->total_amount,
                'discount_total' => (float) $order->discount_total,
                'final_amount' => (float) $order->final_amount,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'pickup_address' => $order->pickup_address,
                'delivery_address' => $order->delivery_address,
                'pickup_time' => $order->pickup_time?->format('d M Y, H:i'),
                'delivered_time' => $order->delivered_time?->format('d M Y, H:i'),
                'created_at' => $order->created_at?->format('d M Y, H:i'),
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'full_name' => $order->customer->full_name,
                    'phone_number' => $order->customer->phone_number,
                    'default_address' => $order->customer->default_address,
                ] : null,
                'items' => $order->items->map(function (OrderItem $item) {
                    $photosBefore = $item->photos
                        ->where('photo_type', 'BEFORE')
                        ->values()
                        ->map(fn (OrderItemPhoto $photo) => [
                            'id' => $photo->id,
                            'photo_type' => $photo->photo_type,
                            'photo_path' => $photo->photo_path,
                            'url' => Storage::url($photo->photo_path),
                            'created_at' => $photo->created_at?->format('d M Y, H:i'),
                        ]);

                    $photosAfter = $item->photos
                        ->where('photo_type', 'AFTER')
                        ->values()
                        ->map(fn (OrderItemPhoto $photo) => [
                            'id' => $photo->id,
                            'photo_type' => $photo->photo_type,
                            'photo_path' => $photo->photo_path,
                            'url' => Storage::url($photo->photo_path),
                            'created_at' => $photo->created_at?->format('d M Y, H:i'),
                        ]);

                    $photosDefect = $item->photos
                        ->where('photo_type', 'DEFECT')
                        ->values()
                        ->map(fn (OrderItemPhoto $photo) => [
                            'id' => $photo->id,
                            'photo_type' => $photo->photo_type,
                            'photo_path' => $photo->photo_path,
                            'url' => Storage::url($photo->photo_path),
                            'created_at' => $photo->created_at?->format('d M Y, H:i'),
                        ]);

                    return [
                        'id' => $item->id,
                        'item_tag_code' => $item->item_tag_code,
                        'shoe_brand_model' => $item->shoe_brand_model,
                        'shoe_material' => $item->shoe_material,
                        'shoe_color' => $item->shoe_color,
                        'defect_notes' => $item->defect_notes,
                        'storage_shelf_number' => $item->storage_shelf_number,
                        'status' => $item->status,
                        'estimated_completion' => $item->estimated_completion?->format('d M Y, H:i'),
                        'completed_at' => $item->completed_at?->format('d M Y, H:i'),
                        'created_at' => $item->created_at?->format('d M Y, H:i'),
                        'service' => $item->service ? [
                            'id' => $item->service->id,
                            'service_name' => $item->service->service_name,
                            'base_price' => (float) $item->service->base_price,
                        ] : null,
                        'qc' => $item->qc ? [
                            'id' => $item->qc->id,
                            'qc_passed' => (bool) $item->qc->qc_passed,
                            'inspected_at' => $item->qc->inspected_at?->format('d M Y, H:i'),
                        ] : null,
                        'is_qc_passed' => (bool) ($item->qc?->qc_passed ?? false),
                        'qc_passed' => (bool) ($item->qc?->qc_passed ?? false),
                        'photos_before' => $photosBefore,
                        'photos_after' => $photosAfter,
                        'photos_defect' => $photosDefect,
                        'before_photos' => $photosBefore,
                        'after_photos' => $photosAfter,
                    ];
                }),
                'is_qc_passed' => $order->items->isNotEmpty() && $order->items->every(fn (OrderItem $item) => (bool) ($item->qc?->qc_passed ?? false)),
            ];
        });

        return Inertia::render('Tracking/Index', [
            'orders' => $formattedOrders,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
