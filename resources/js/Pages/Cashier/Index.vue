<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ThermalPrintModal from '@/Components/ThermalPrintModal.vue';
import {
    Plus,
    Printer,
    CheckCircle2,
    Clock,
    Sparkles,
    PackagePlus,
    Check,
    AlertCircle,
    User,
    Phone,
    CreditCard,
    ArrowRight,
    Search,
    X,
    AlertTriangle,
} from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({
            today_in: 0,
            in_workshop: 0,
            ready_pickup: 0,
            completed_today: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            tab: 'READY',
        }),
    },
});

const searchQuery = ref(props.filters.search || '');
const activeTab = ref(props.filters.tab || 'READY');
const printModalOpen = ref(false);
const printOrder = ref(null);
const handoverModalOpen = ref(false);
const selectedHandoverItem = ref(null);
const isHandingOver = ref(false);

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const handleSearch = () => {
    router.get(
        route('cashier.index'),
        { search: searchQuery.value, tab: activeTab.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const setTab = (tabKey) => {
    activeTab.value = tabKey;
    router.get(
        route('cashier.index'),
        { search: searchQuery.value, tab: tabKey },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const openPrintModal = (item) => {
    if (item.print_order) {
        printOrder.value = item.print_order;
    } else {
        printOrder.value = {
            order_code: item.order_code,
            created_at: item.created_at_formatted || 'Hari ini',
            final_amount: item.service_price || 0,
            total_amount: item.order_total || item.service_price || 0,
            payment_method: item.payment_method || 'QRIS',
            payment_status: item.payment_status || 'UNPAID',
            customer: {
                full_name: item.customer_name || 'Pelanggan Walk-In',
                phone_number: item.customer_phone || '-',
            },
            items: [
                {
                    item_tag_code: item.item_tag_code,
                    storage_shelf_number: item.storage_shelf_number,
                    shoe_brand_model: item.shoe_brand_model,
                    shoe_material: item.shoe_material,
                    shoe_color: item.shoe_color,
                    defect_notes: item.defect_notes,
                    service: {
                        service_name: item.service_name,
                        base_price: item.service_price,
                    },
                },
            ],
        };
    }
    printModalOpen.value = true;
};

const openHandoverModal = (item) => {
    selectedHandoverItem.value = item;
    handoverModalOpen.value = true;
};

const confirmHandover = () => {
    if (!selectedHandoverItem.value || isHandingOver.value) return;

    isHandingOver.value = true;
    router.post(
        route('cashier.items.handover', selectedHandoverItem.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isHandingOver.value = false;
                handoverModalOpen.value = false;
                selectedHandoverItem.value = null;
            },
        }
    );
};

const getWorkshopStatusBadge = (status) => {
    switch (status) {
        case 'CHECKED_IN':
            return {
                label: 'Antrean Masuk',
                badgeClass: 'bg-amber-100 text-amber-900 border-amber-300',
            };
        case 'WASHING':
            return {
                label: 'Sedang Dicuci & Sikat',
                badgeClass: 'bg-blue-100 text-blue-900 border-blue-300',
            };
        case 'DRYING':
            return {
                label: 'Pos Pengeringan',
                badgeClass: 'bg-indigo-100 text-indigo-900 border-indigo-300',
            };
        case 'QC_INSPECTION':
            return {
                label: 'Pemeriksaan QC',
                badgeClass: 'bg-purple-100 text-purple-900 border-purple-300',
            };
        case 'READY_DELIVERY':
            return {
                label: 'Siap Diambil',
                badgeClass: 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold animate-pulse',
            };
        case 'DELIVERED':
            return {
                label: 'Telah Diserahkan',
                badgeClass: 'bg-neutral-100 text-neutral-700 border-neutral-300',
            };
        default:
            return {
                label: status || 'Diproses',
                badgeClass: 'bg-neutral-100 text-neutral-700 border-neutral-200',
            };
    }
};

const getSlaBadgeClass = (slaStatus) => {
    switch (slaStatus) {
        case 'overdue':
            return 'bg-red-500 text-white font-bold';
        case 'urgent':
            return 'bg-amber-400 text-amber-950 font-bold';
        case 'warning':
            return 'bg-amber-100 text-amber-800 font-medium';
        default:
            return 'bg-emerald-100 text-emerald-800 font-medium';
    }
};
</script>

<template>
    <AuthenticatedLayout
        :search="searchQuery"
        @update:search="searchQuery = $event"
        @search="handleSearch"
    >
        <Head title="Kasir & Front Desk - Spatoe" />

        <div class="min-h-screen bg-neutral-100/70 pb-16">
            <!-- Top Header & Mini Metrics -->
            <div class="border-b border-neutral-200 bg-white shadow-xs">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">
                    <!-- Baris Atas: Judul & Tombol Utama + Terima Sepatu Baru -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="h-3.5 w-3.5 rounded-full bg-[#6B0B23]" />
                                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-neutral-900">
                                    Front Desk & Kasir
                                </h1>
                                <span class="text-xs font-semibold text-neutral-400 hidden sm:inline">/ Manajemen Antrean & Serah Terima</span>
                            </div>
                            <p class="mt-1 text-xs sm:text-sm text-neutral-500 max-w-2xl">
                                Kelola intake sepatu pelanggan walk-in, pantau progres pengerjaan workshop, dan proses serah terima pengambilan sepatu.
                            </p>
                        </div>

                        <!-- Tombol Primer: + Terima Sepatu Baru -->
                        <Link
                            :href="route('workshop.checkin')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#6B0B23] px-6 py-3.5 text-sm font-extrabold text-white shadow-md transition hover:bg-[#54081B] active:scale-98 whitespace-nowrap cursor-pointer"
                        >
                            <Plus class="h-5 w-5 stroke-[2.5]" />
                            <span>Terima Sepatu Baru</span>
                        </Link>
                    </div>

                    <!-- 4 Kartu Ringkasan Metrik Kasir -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                        <!-- Metrik 1: Masuk Hari Ini -->
                        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Masuk Hari Ini</span>
                                <div class="rounded-xl bg-neutral-100 p-2 text-neutral-700">
                                    <PackagePlus class="h-4 w-4" />
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-black text-neutral-900">{{ metrics.today_in }}</p>
                            <p class="text-[11px] text-neutral-500">pasang sepatu intake</p>
                        </div>

                        <!-- Metrik 2: Sedang di Workshop -->
                        <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-4 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Di Workshop</span>
                                <div class="rounded-xl bg-blue-100 p-2 text-blue-700">
                                    <Sparkles class="h-4 w-4" />
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-black text-blue-900">{{ metrics.in_workshop }}</p>
                            <p class="text-[11px] text-blue-700">sedang dicuci / dikeringkan</p>
                        </div>

                        <!-- Metrik 3: Siap Diambil Pelanggan -->
                        <div class="rounded-2xl border border-emerald-300 bg-emerald-50/60 p-4 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Siap Diambil</span>
                                <div class="rounded-xl bg-emerald-200/80 p-2 text-emerald-800">
                                    <CheckCircle2 class="h-4 w-4" />
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-black text-emerald-950">{{ metrics.ready_pickup }}</p>
                            <p class="text-[11px] font-semibold text-emerald-800">siap serah terima kasir</p>
                        </div>

                        <!-- Metrik 4: Selesai Hari Ini -->
                        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Selesai Hari Ini</span>
                                <div class="rounded-xl bg-neutral-200/80 p-2 text-neutral-700">
                                    <Check class="h-4 w-4" />
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-black text-neutral-900">{{ metrics.completed_today }}</p>
                            <p class="text-[11px] text-neutral-500">telah diambil pelanggan</p>
                        </div>
                    </div>

                    <!-- Toolbar Filter Tab Status -->
                    <div class="border-t border-neutral-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                            <button
                                type="button"
                                @click="setTab('READY')"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-2xs whitespace-nowrap cursor-pointer',
                                    activeTab === 'READY'
                                        ? 'bg-[#6B0B23] text-white ring-2 ring-[#6B0B23]/20'
                                        : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                                ]"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                <span>Siap Diambil Pelanggan</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded-full text-[10px] font-extrabold',
                                        activeTab === 'READY' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'
                                    ]"
                                >
                                    {{ metrics.ready_pickup }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="setTab('WORKSHOP')"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-2xs whitespace-nowrap cursor-pointer',
                                    activeTab === 'WORKSHOP'
                                        ? 'bg-[#6B0B23] text-white ring-2 ring-[#6B0B23]/20'
                                        : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                                ]"
                            >
                                <Sparkles class="h-3.5 w-3.5" />
                                <span>Sedang Diproses Workshop</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded-full text-[10px] font-extrabold',
                                        activeTab === 'WORKSHOP' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-800'
                                    ]"
                                >
                                    {{ metrics.in_workshop }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="setTab('ALL')"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-2xs whitespace-nowrap cursor-pointer',
                                    activeTab === 'ALL'
                                        ? 'bg-[#6B0B23] text-white ring-2 ring-[#6B0B23]/20'
                                        : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                                ]"
                            >
                                <span>Semua Data Antrean</span>
                            </button>
                        </div>

                        <!-- Info Total Ditampilkan -->
                        <div class="text-xs font-semibold text-neutral-500">
                            Menampilkan <span class="font-bold text-neutral-900">{{ items.length }}</span> sepatu
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area: Data Table -->
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                <!-- Empty State -->
                <div
                    v-if="items.length === 0"
                    class="rounded-3xl border border-dashed border-neutral-300 bg-white p-12 text-center shadow-xs"
                >
                    <CheckCircle2 class="mx-auto h-12 w-12 text-neutral-300 mb-3" />
                    <h3 class="text-base font-bold text-neutral-800">Tidak ada sepatu dalam daftar antrean</h3>
                    <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                        Tidak ada data yang sesuai dengan filter atau kata kunci pencarian saat ini.
                    </p>
                    <div class="mt-5">
                        <Link
                            :href="route('workshop.checkin')"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#6B0B23] px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-[#54081B]"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Terima Sepatu Baru Sekarang</span>
                        </Link>
                    </div>
                </div>

                <!-- Table Container -->
                <div v-else class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-black uppercase tracking-wider text-neutral-500">
                                    <th class="py-3.5 px-4">Tag & Lokasi Rak</th>
                                    <th class="py-3.5 px-4">Pelanggan</th>
                                    <th class="py-3.5 px-4">Sepatu & Paket</th>
                                    <th class="py-3.5 px-4">Status Workshop</th>
                                    <th class="py-3.5 px-4">Pembayaran</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 text-xs">
                                <tr
                                    v-for="item in items"
                                    :key="item.id"
                                    class="hover:bg-neutral-50/80 transition-colors"
                                >
                                    <!-- 1. Tag & Rak -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="rounded bg-neutral-900 px-2 py-0.5 font-mono text-[11px] font-black tracking-wider text-white">
                                                    {{ item.item_tag_code }}
                                                </span>
                                                <span
                                                    v-if="item.storage_shelf_number"
                                                    class="rounded bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 text-[10px] font-bold text-neutral-700"
                                                >
                                                    Rak {{ item.storage_shelf_number }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-neutral-400 font-mono">
                                                Nota: {{ item.order_code }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- 2. Pelanggan -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="space-y-0.5">
                                            <p class="font-bold text-neutral-900 flex items-center gap-1">
                                                <User class="h-3 w-3 text-neutral-400" />
                                                <span>{{ item.customer_name }}</span>
                                            </p>
                                            <p class="text-[11px] text-neutral-500 font-mono flex items-center gap-1">
                                                <Phone class="h-3 w-3 text-neutral-400" />
                                                <span>{{ item.customer_phone }}</span>
                                            </p>
                                        </div>
                                    </td>

                                    <!-- 3. Sepatu & Paket Layanan -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="space-y-1 max-w-[200px]">
                                            <p class="font-bold text-neutral-900 truncate">
                                                {{ item.shoe_brand_model }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span class="rounded bg-[#6B0B23] px-2 py-0.5 text-[10px] font-bold text-white">
                                                    {{ item.service_name }}
                                                </span>
                                                <span class="text-[10px] text-neutral-500">
                                                    {{ [item.shoe_material, item.shoe_color].filter(Boolean).join(' • ') }}
                                                </span>
                                            </div>
                                            <!-- Catatan defect jika ada -->
                                            <div
                                                v-if="item.defect_notes"
                                                class="rounded bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[9.5px] font-medium text-amber-800 flex items-start gap-1"
                                            >
                                                <AlertTriangle class="h-3 w-3 text-amber-600 shrink-0 mt-0.5" />
                                                <span class="line-clamp-1">{{ item.defect_notes }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 4. Status Workshop & SLA -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="space-y-1.5">
                                            <div>
                                                <span
                                                    class="inline-block rounded-full border px-2.5 py-0.5 text-[10px] font-bold shadow-2xs"
                                                    :class="getWorkshopStatusBadge(item.status).badgeClass"
                                                >
                                                    {{ getWorkshopStatusBadge(item.status).label }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1 text-[10px] text-neutral-400">
                                                <Clock class="h-3 w-3" />
                                                <span
                                                    class="rounded-md px-1.5 py-0.2 text-[9.5px]"
                                                    :class="getSlaBadgeClass(item.sla_status)"
                                                >
                                                    {{ item.sla_text }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 5. Pembayaran -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="space-y-1">
                                            <p class="font-extrabold text-neutral-900">
                                                {{ formatRupiah(item.order_total) }}
                                            </p>
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    :class="[
                                                        'rounded px-1.5 py-0.5 text-[9.5px] font-black tracking-wider uppercase',
                                                        item.payment_status === 'PAID'
                                                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                                            : 'bg-rose-100 text-rose-800 border border-rose-300'
                                                    ]"
                                                >
                                                    {{ item.payment_status === 'PAID' ? 'LUNAS' : 'BELUM LUNAS' }}
                                                </span>
                                                <span class="text-[10px] text-neutral-400 font-mono">
                                                    {{ item.payment_method }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 6. Tombol Aksi -->
                                    <td class="py-3.5 px-4 align-top text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Tombol Cetak Struk Kasir / Tag -->
                                            <button
                                                type="button"
                                                @click="openPrintModal(item)"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-neutral-300 bg-white px-3 py-1.5 text-xs font-bold text-neutral-700 shadow-2xs hover:bg-neutral-50 hover:text-neutral-900 transition-colors cursor-pointer"
                                                title="Cetak Struk Kasir & Label Tag Rak"
                                            >
                                                <Printer class="h-3.5 w-3.5 text-neutral-600" />
                                                <span>Cetak</span>
                                            </button>

                                            <!-- Tombol Serahkan Sepatu (Hanya saat status READY_DELIVERY) -->
                                            <button
                                                v-if="item.status === 'READY_DELIVERY'"
                                                type="button"
                                                @click="openHandoverModal(item)"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 text-xs font-extrabold shadow-xs transition-all cursor-pointer active:scale-95"
                                            >
                                                <CheckCircle2 class="h-3.5 w-3.5" />
                                                <span>Serahkan Sepatu</span>
                                            </button>

                                            <span
                                                v-else-if="item.status === 'DELIVERED'"
                                                class="text-[11px] font-bold text-neutral-400 italic"
                                            >
                                                Selesai
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Serah Terima Sepatu -->
        <div
            v-if="handoverModalOpen && selectedHandoverItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
            @click.self="handoverModalOpen = false"
        >
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-neutral-200">
                <div class="flex items-center gap-3 border-b border-neutral-100 pb-4">
                    <div class="rounded-2xl bg-emerald-100 p-2.5 text-emerald-700">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-black text-neutral-900">Serah Terima ke Pelanggan</h3>
                        <p class="text-xs text-neutral-500">Konfirmasi penyelesaian pesanan & serah sepatu</p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <div class="rounded-2xl bg-neutral-50 p-4 border border-neutral-200/80 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500">Nomor Tag:</span>
                            <span class="font-mono font-black text-sm text-neutral-900">{{ selectedHandoverItem.item_tag_code }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500">Model Sepatu:</span>
                            <span class="font-bold text-neutral-900">{{ selectedHandoverItem.shoe_brand_model }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500">Nama Pelanggan:</span>
                            <span class="font-bold text-neutral-900">{{ selectedHandoverItem.customer_name }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-neutral-200 pt-2">
                            <span class="text-neutral-500">Total Tagihan:</span>
                            <span class="font-black text-sm text-neutral-900">{{ formatRupiah(selectedHandoverItem.order_total) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500">Status Pembayaran:</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded font-black text-[10px]',
                                    selectedHandoverItem.payment_status === 'PAID'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-rose-100 text-rose-800'
                                ]"
                            >
                                {{ selectedHandoverItem.payment_status === 'PAID' ? 'LUNAS' : 'BELUM DIBAYAR' }}
                            </span>
                        </div>
                    </div>

                    <!-- Peringatan jika belum lunas -->
                    <div
                        v-if="selectedHandoverItem.payment_status !== 'PAID'"
                        class="rounded-xl bg-amber-50 border border-amber-300 p-3 text-amber-900 flex items-start gap-2 leading-relaxed"
                    >
                        <AlertCircle class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold text-amber-900">Perhatian Kasir:</p>
                            <p class="text-[11px] text-amber-800">
                                Pastikan pelanggan telah melunasi tagihan sejumlah <strong>{{ formatRupiah(selectedHandoverItem.order_total) }}</strong> sebelum menyerahkan sepatu.
                            </p>
                        </div>
                    </div>

                    <p class="text-[11px] text-neutral-500 italic">
                        Menekan tombol konfirmasi akan memperbarui status sepatu menjadi <strong>DELIVERED</strong> dan status nota menjadi <strong>LUNAS</strong>.
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="handoverModalOpen = false"
                        class="rounded-xl border border-neutral-300 px-4 py-2.5 text-xs font-bold text-neutral-700 hover:bg-neutral-100 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmHandover"
                        :disabled="isHandingOver"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-xs font-extrabold text-white shadow-md transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <Check class="h-4 w-4" />
                        <span>{{ isHandingOver ? 'Memproses...' : 'Konfirmasi & Serahkan' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Cetak Thermal & Label Tag Rak -->
        <ThermalPrintModal
            :is-open="printModalOpen"
            :order="printOrder"
            @close="printModalOpen = false"
        />
    </AuthenticatedLayout>
</template>

