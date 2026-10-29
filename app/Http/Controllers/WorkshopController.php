<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopController extends Controller
{
    /**
     * Display the Kanban Board workshop floor.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $activeStatuses = ['CHECKED_IN', 'WASHING', 'DRYING', 'QC_INSPECTION', 'READY_DELIVERY'];

        $query = OrderItem::query()
            ->whereIn('status', $activeStatuses)
            ->with([
                'order.customer:id,full_name,phone_number',
                'service:id,service_name,estimated_hours',
                'technician:id,name',
                'statusLogs.user:id,name',
                'photos' => function ($photoQuery) {
                    $photoQuery->whereIn('photo_type', ['BEFORE', 'DEFECT'])
                        ->select(['id', 'order_item_id', 'photo_type', 'photo_path', 'created_at']);
                },
                'qc:id,order_item_id,qc_passed',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('item_tag_code', 'like', "%{$search}%")
                    ->orWhere('shoe_brand_model', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('estimated_completion', 'asc')->get();

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

            return [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'order_code' => $item->order?->order_code,
                'customer_name' => $item->order?->customer?->full_name,
                'customer_phone' => $item->order?->customer?->phone_number,
                'item_tag_code' => $item->item_tag_code,
                'shoe_brand_model' => $item->shoe_brand_model,
                'shoe_material' => $item->shoe_material,
                'shoe_color' => $item->shoe_color,
                'defect_notes' => $item->defect_notes,
                'storage_shelf_number' => $item->storage_shelf_number,
                'status' => $item->status,
                'estimated_completion' => $item->estimated_completion?->format('d M Y, H:i'),
                'estimated_completion_iso' => $item->estimated_completion?->toIso8601String(),
                'sla_status' => $slaStatus,
                'sla_text' => $slaText,
                'completed_at' => $item->completed_at?->format('d M Y, H:i'),
                'service' => $item->service ? [
                    'id' => $item->service->id,
                    'service_name' => $item->service->service_name,
                    'estimated_hours' => $item->service->estimated_hours,
                ] : null,
                'technician' => $item->technician ? [
                    'id' => $item->technician->id,
                    'name' => $item->technician->name,
                ] : null,
                'qc' => $item->qc ? [
                    'id' => $item->qc->id,
                    'qc_passed' => (bool) $item->qc->qc_passed,
                ] : null,
                'photos' => $item->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'photo_type' => $photo->photo_type,
                    'photo_path' => $photo->photo_path,
                    'url' => Storage::url($photo->photo_path),
                ]),
            ];
        });

        $groupedItems = [];
        foreach ($activeStatuses as $status) {
            $groupedItems[$status] = $formattedItems->where('status', $status)->values()->all();
        }

        $totalActive = $formattedItems->count();
        $needsWashing = $formattedItems->where('status', 'CHECKED_IN')->count();
        $urgentCount = $formattedItems->whereIn('sla_status', ['urgent', 'overdue'])->count();

        $user = $request->user();
        $isAdminOrSupervisor = $user && in_array($user->role, ['admin', 'supervisor']);

        $staffList = [];
        if ($isAdminOrSupervisor) {
            $staffList = User::where('role', 'technician')
                ->select(['id', 'name', 'email', 'role', 'assigned_station'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'role' => $staff->role,
                    'assigned_station' => $staff->assigned_station ?? 'ALL',
                ])
                ->all();
        }

        return Inertia::render('Workshop/Index', [
            'items' => $formattedItems,
            'grouped_items' => $groupedItems,
            'metrics' => [
                'total_active' => $totalActive,
                'needs_washing' => $needsWashing,
                'urgent_count' => $urgentCount,
            ],
            'filters' => [
                'search' => $search,
            ],
            'staffList' => $staffList,
            'staff_list' => $staffList,
        ]);
    }

    /**
     * Update order item status from Kanban / Assembly line.
     */
    public function updateStatus(Request $request, OrderItem $item): RedirectResponse
    {
        $statusValue = $request->input('status') ?? $request->input('new_status');
        $request->merge([
            'status' => $statusValue,
            'new_status' => $statusValue,
        ]);

        $validStatuses = [
            'WAITING_PICKUP',
            'CHECKED_IN',
            'WASHING',
            'DRYING',
            'QC_INSPECTION',
            'READY_DELIVERY',
            'DELIVERED',
            'CANCELLED',
        ];

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($validStatuses)],
            'new_status' => ['required', 'string', Rule::in($validStatuses)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $item->status;
        $newStatus = $validated['status'];
        $technicianId = $request->user()?->id ?? Auth::id();

        // Validasi transisi status berurutan (mencegah loncatan ilegal)
        $allowedTransitions = [
            'WAITING_PICKUP' => ['CHECKED_IN', 'CANCELLED'],
            'CHECKED_IN' => ['WASHING', 'CANCELLED'],
            'WASHING' => ['DRYING', 'CHECKED_IN', 'CANCELLED'],
            'DRYING' => ['QC_INSPECTION', 'WASHING', 'CANCELLED'],
            'QC_INSPECTION' => ['READY_DELIVERY', 'WASHING', 'DRYING', 'CANCELLED'],
            'READY_DELIVERY' => ['DELIVERED', 'QC_INSPECTION', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];

        if ($previousStatus !== $newStatus && ! in_array($newStatus, $allowedTransitions[$previousStatus] ?? [])) {
            throw ValidationException::withMessages([
                'status' => "Loncatan status tidak diizinkan: Sepatu berstatus {$previousStatus} tidak dapat langsung dialihkan ke {$newStatus}.",
            ]);
        }

        DB::transaction(function () use ($item, $newStatus, $previousStatus, $technicianId, $validated) {
            $updateData = [
                'status' => $newStatus,
                'technician_id' => $technicianId,
            ];

            if (in_array($newStatus, ['READY_DELIVERY', 'DELIVERED']) && ! $item->completed_at) {
                $updateData['completed_at'] = now();
            }

            $item->update($updateData);

            $item->statusLogs()->create([
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'notes' => $validated['notes'] ?? "Status dialihkan ke {$newStatus} di lantai workshop",
                'changed_by' => $technicianId,
            ]);
        });

        return back()->with('success', "Status sepatu {$item->item_tag_code} berhasil diubah ke {$newStatus}.");
    }

    /**
     * Update weekly station assignments for workshop staff (admin/supervisor only).
     */
    public function updateStationAssignments(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'supervisor'])) {
            abort(403, 'Hanya administrator atau supervisor yang dapat mengatur penugasan stasiun staf.');
        }

        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.id' => ['required', 'integer', 'exists:users,id'],
            'assignments.*.assigned_station' => [
                'required',
                'string',
                Rule::in(['ALL', 'WASHING', 'DRYING', 'QC_INSPECTION', 'READY_DELIVERY']),
            ],
        ], [
            'assignments.required' => 'Daftar penugasan staf wajib dikirimkan.',
            'assignments.*.id.exists' => 'Data staf tidak ditemukan.',
            'assignments.*.assigned_station.in' => 'Pilihan stasiun tugas tidak valid.',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['assignments'] as $assignment) {
                User::where('id', $assignment['id'])->update([
                    'assigned_station' => $assignment['assigned_station'],
                ]);
            }
        });

        return back()->with('success', 'Penugasan stasiun staf mingguan berhasil diperbarui.');
    }
}
