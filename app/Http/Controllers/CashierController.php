<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashierController extends Controller
{
    /**
     * Display the Cashier / Front Desk dashboard.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $tab = strtoupper(trim((string) $request->input('tab', 'READY'))); // 'READY' | 'WORKSHOP' | 'ALL'

        // 1. Ringkasan Metrik Kasir
        $todayIn = OrderItem::whereDate('created_at', today())->count();
        $inWorkshop = OrderItem::whereIn('status', ['CHECKED_IN', 'WASHING', 'DRYING', 'QC_INSPECTION'])->count();
        $readyPickup = OrderItem::where('status', 'READY_DELIVERY')->count();
        $completedToday = OrderItem::where('status', 'DELIVERED')
            ->where(function ($q) {
                $q->whereDate('completed_at', today())
                    ->orWhereDate('updated_at', today());
            })
            ->count();

        // 2. Query Antrean Sepatu
        $query = OrderItem::query()
            ->with([
                'order.customer:id,full_name,phone_number',
                'order.items.service:id,service_name,base_price',
                'service:id,service_name,base_price,estimated_hours',
                'technician:id,name',
            ]);

        // Filter Tab
        if ($tab === 'READY') {
            $query->where('status', 'READY_DELIVERY');
        } elseif ($tab === 'WORKSHOP') {
            $query->whereIn('status', ['CHECKED_IN', 'WASHING', 'DRYING', 'QC_INSPECTION']);
        } else {
            // ALL: tampilkan aktif dan yang selesai hari ini
            $query->where(function ($q) {
                $q->whereIn('status', ['CHECKED_IN', 'WASHING', 'DRYING', 'QC_INSPECTION', 'READY_DELIVERY'])
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'DELIVERED')
                            ->whereDate('updated_at', today());
                    });
            });
        }

        // Pencarian (Tag, Model, Nama Pelanggan, No HP, No Nota)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('item_tag_code', 'like', "%{$search}%")
                    ->orWhere('shoe_brand_model', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_code', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('full_name', 'like', "%{$search}%")
                                    ->orWhere('phone_number', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $items = $query->orderByRaw("FIELD(status, 'READY_DELIVERY', 'CHECKED_IN', 'WASHING', 'DRYING', 'QC_INSPECTION', 'DELIVERED')")
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedItems = $items->map(function (OrderItem $item) {
            $slaStatus = 'safe';
            $slaText = 'Tidak ada SLA';

            if ($item->estimated_completion) {
                $now = now();
                $diffMinutes = $now->diffInMinutes($item->estimated_completion, false);

                if ($diffMinutes < 0) {
                    $slaStatus = 'overdue';
                    $slaText = 'Terlambat '.abs(round($diffMinutes / 60, 1)).' jam';
                } elseif ($diffMinutes <= 360) {
                    $slaStatus = 'urgent';
                    $slaText = round($diffMinutes / 60, 1).' jam lagi';
                } elseif ($diffMinutes <= 720) {
                    $slaStatus = 'warning';
                    $slaText = round($diffMinutes / 60, 1).' jam lagi';
                } else {
                    $slaStatus = 'safe';
                    $slaText = round($diffMinutes / 60, 1).' jam lagi';
                }
            }

            // Order Payload untuk Cetak Struk via ThermalPrintModal
            $printOrder = null;
            if ($item->order) {
                $printOrder = [
                    'order_code' => $item->order->order_code,
                    'created_at' => $item->order->created_at?->format('d M Y, H:i') ?? 'Hari ini',
                    'total_amount' => (float) ($item->order->total_amount ?? 0),
                    'final_amount' => (float) ($item->order->final_amount ?? 0),
                    'payment_method' => $item->order->payment_method ?? 'QRIS',
                    'payment_status' => $item->order->payment_status ?? 'UNPAID',
                    'customer' => [
                        'full_name' => $item->order->customer?->full_name ?? 'Pelanggan Walk-In',
                        'phone_number' => $item->order->customer?->phone_number ?? '-',
                    ],
                    'items' => $item->order->items->map(fn ($oi) => [
                        'item_tag_code' => $oi->item_tag_code,
                        'storage_shelf_number' => $oi->storage_shelf_number,
                        'shoe_brand_model' => $oi->shoe_brand_model,
                        'shoe_material' => $oi->shoe_material,
                        'shoe_color' => $oi->shoe_color,
                        'defect_notes' => $oi->defect_notes,
                        'service' => $oi->service ? [
                            'service_name' => $oi->service->service_name,
                            'base_price' => (float) $oi->service->base_price,
                        ] : null,
                    ])->all(),
                ];
            }

            return [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'order_code' => $item->order?->order_code ?? '-',
                'item_tag_code' => $item->item_tag_code,
                'storage_shelf_number' => $item->storage_shelf_number,
                'shoe_brand_model' => $item->shoe_brand_model,
                'shoe_material' => $item->shoe_material,
                'shoe_color' => $item->shoe_color,
                'defect_notes' => $item->defect_notes,
                'status' => $item->status,
                'customer_name' => $item->order?->customer?->full_name ?? 'Walk-In',
                'customer_phone' => $item->order?->customer?->phone_number ?? '-',
                'service_name' => $item->service?->service_name ?? 'Treatment',
                'service_price' => (float) ($item->service?->base_price ?? 0),
                'technician_name' => $item->technician?->name ?? 'Belum Ditugaskan',
                'payment_status' => $item->order?->payment_status ?? 'UNPAID',
                'payment_method' => $item->order?->payment_method ?? 'QRIS',
                'order_total' => (float) ($item->order?->final_amount ?? $item->order?->total_amount ?? 0),
                'created_at_formatted' => $item->created_at?->format('d M, H:i') ?? '-',
                'sla_status' => $slaStatus,
                'sla_text' => $slaText,
                'print_order' => $printOrder,
            ];
        });

        return Inertia::render('Cashier/Index', [
            'items' => $formattedItems,
            'metrics' => [
                'today_in' => $todayIn,
                'in_workshop' => $inWorkshop,
                'ready_pickup' => $readyPickup,
                'completed_today' => $completedToday,
            ],
            'filters' => [
                'search' => $search,
                'tab' => $tab,
            ],
        ]);
    }

    /**
     * Handover / serahkan sepatu ke pelanggan dan selesaikan transaksi.
     */
    public function handover(Request $request, OrderItem $item): RedirectResponse
    {
        if ($item->status !== 'READY_DELIVERY') {
            return back()->withErrors([
                'status' => "Sepatu berstatus {$item->status} belum siap diambil pelanggan (wajib lolos QC dan berstatus Siap Antar).",
            ]);
        }

        $cashierId = $request->user()?->id;

        DB::transaction(function () use ($item, $cashierId) {
            $item->update([
                'status' => 'DELIVERED',
                'completed_at' => now(),
            ]);

            if ($item->order) {
                $item->order->update([
                    'payment_status' => 'PAID',
                    'delivered_time' => now(),
                ]);
            }

            $item->statusLogs()->create([
                'previous_status' => 'READY_DELIVERY',
                'new_status' => 'DELIVERED',
                'notes' => 'Sepatu telah diserahkan ke pelanggan oleh kasir (Handover Selesai)',
                'changed_by' => $cashierId,
            ]);
        });

        return back()->with('success', "Sepatu [{$item->item_tag_code}] berhasil diserahkan ke pelanggan dan nota dinyatakan lunas.");
    }
}
