<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ThermalPrintModal from '@/Components/ThermalPrintModal.vue';
import StationAssignmentModal from '@/Components/StationAssignmentModal.vue';
import {
    LayoutGrid,
    Printer,
    Plus,
    Sparkles,
    Wind,
    ShieldCheck,
    Truck,
    Clock,
    AlertTriangle,
    Package,
    Search,
    ChevronRight,
    CheckCircle2,
    RotateCcw,
    X,
    User,
    Users,
    Check,
    Lock,
} from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    grouped_items: {
        type: Object,
        default: () => ({
            CHECKED_IN: [],
            WASHING: [],
            DRYING: [],
            QC_INSPECTION: [],
            READY_DELIVERY: [],
        }),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_active: 0,
            needs_washing: 0,
            urgent_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
    staffList: {
        type: Array,
        default: () => [],
    },
    staff_list: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isAdmin = computed(() => ['admin', 'supervisor'].includes(currentUser.value?.role));

const searchQuery = ref(props.filters.search || '');
const updatingItemId = ref(null);
const selectedDefectItem = ref(null);
const selectedQcItem = ref(null);
const printModalOpen = ref(false);
const printOrder = ref(null);
const stationModalOpen = ref(false);
const activeTab = ref('all'); // 'all' | 'wash' | 'dry' | 'qc' | 'ready'

// Helper penugasan stasiun: Cek apakah teknisi berhak mengeksekusi aksi di stasiun ini
const canOperateStation = (stationKey) => {
    if (isAdmin.value) return true;
    const userStation = currentUser.value?.assigned_station || 'ALL';
    if (userStation === 'ALL') return true;

    const normalizedKey = (stationKey || '').toUpperCase();
    if (normalizedKey === 'CHECKED_IN' || normalizedKey === 'WASHING' || normalizedKey === 'WASH') {
        return userStation === 'WASHING';
    }
    if (normalizedKey === 'DRYING' || normalizedKey === 'DRY') {
        return userStation === 'DRYING';
    }
    if (normalizedKey === 'QC_INSPECTION' || normalizedKey === 'QC') {
        return userStation === 'QC_INSPECTION';
    }
    if (normalizedKey === 'READY_DELIVERY' || normalizedKey === 'READY') {
        return userStation === 'READY_DELIVERY';
    }
    return userStation === normalizedKey;
};

// Aliaskan isActionPermitted ke canOperateStation untuk kompatibilitas
const isActionPermitted = (columnKey, item = null) => {
    return canOperateStation(columnKey || item?.status);
};

const getStationBadgeLabel = (station) => {
    switch (station) {
        case 'WASHING':
            return '🧼 Pos Cuci & Sikat';
        case 'DRYING':
            return '💨 Pos Pengeringan';
        case 'QC_INSPECTION':
            return '🛡️ Pos QC';
        case 'READY_DELIVERY':
            return '🚚 Siap Antar';
        case 'ALL':
        default:
            return '🔄 Semua Pos (Rotasi Bebas)';
    }
};

// Tab Filter Stasiun Kerja (Assembly Line)
const WORKSTATION_TABS = [
    { key: 'all', label: 'Semua Pos (Overview)', icon: LayoutGrid },
    { key: 'wash', label: 'Pos Cuci', icon: Sparkles },
    { key: 'dry', label: 'Pos Pengeringan', icon: Wind },
    { key: 'qc', label: 'Pos QC', icon: ShieldCheck },
    { key: 'ready', label: 'Siap Antar', icon: Truck },
];

const stationCounts = computed(() => {
    const checkedIn = props.grouped_items.CHECKED_IN?.length || 0;
    const washing = props.grouped_items.WASHING?.length || 0;
    const drying = props.grouped_items.DRYING?.length || 0;
    const qc = props.grouped_items.QC_INSPECTION?.length || 0;
    const ready = props.grouped_items.READY_DELIVERY?.length || 0;

    return {
        all: checkedIn + washing + drying + qc + ready,
        wash: checkedIn + washing,
        dry: drying,
        qc: qc,
        ready: ready,
    };
});

const openPrintModal = (item) => {
    printOrder.value = {
        order_code: item.order_code,
        created_at: item.order?.created_at || 'Hari ini',
        final_amount: item.service?.base_price || 0,
        total_amount: item.service?.base_price || 0,
        payment_method: 'QRIS',
        payment_status: 'PAID',
        customer: {
            full_name: item.customer_name || 'Pelanggan Workshop',
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
                service: item.service,
            },
        ],
    };
    printModalOpen.value = true;
};

// 5 Definisi Kolom Alur Pengerjaan
const COLUMNS = [
    {
        key: 'CHECKED_IN',
        title: 'Checked In / Antre',
        subtitle: 'Intake Meja Kasir',
        icon: Package,
        accentColor: 'border-amber-400 bg-amber-500/10 text-amber-700',
        badgeColor: 'bg-amber-100 text-amber-800',
        actionLabel: 'Mulai Cuci',
        nextStatus: 'WASHING',
        actionIcon: Sparkles,
        btnColor: 'bg-amber-600 hover:bg-amber-700 text-white',
    },
    {
        key: 'WASHING',
        title: 'Washing / Pencucian',
        subtitle: 'Treatment & Sikat Noda',
        icon: Sparkles,
        accentColor: 'border-blue-400 bg-blue-500/10 text-blue-700',
        badgeColor: 'bg-blue-100 text-blue-800',
        actionLabel: 'Pindah ke Dryer',
        nextStatus: 'DRYING',
        actionIcon: Wind,
        btnColor: 'bg-blue-600 hover:bg-blue-700 text-white',
    },
    {
        key: 'DRYING',
        title: 'Drying / Pengeringan',
        subtitle: 'Blower & Cold Dryer',
        icon: Wind,
        accentColor: 'border-indigo-400 bg-indigo-500/10 text-indigo-700',
        badgeColor: 'bg-indigo-100 text-indigo-800',
        actionLabel: 'Kirim ke Pos QC',
        nextStatus: 'QC_INSPECTION',
        actionIcon: ShieldCheck,
        btnColor: 'bg-indigo-600 hover:bg-indigo-700 text-white',
    },
    {
        key: 'QC_INSPECTION',
        title: 'QC Inspection',
        subtitle: '6 Parameter Kelayakan',
        icon: ShieldCheck,
        accentColor: 'border-emerald-400 bg-emerald-500/10 text-emerald-700',
        badgeColor: 'bg-emerald-100 text-emerald-800',
        actionLabel: 'Buka Lembar QC',
        nextStatus: 'READY_DELIVERY',
        actionIcon: ShieldCheck,
        btnColor: 'bg-emerald-700 hover:bg-emerald-800 text-white',
    },
    {
        key: 'READY_DELIVERY',
        title: 'Ready / Siap Antar',
        subtitle: 'Packing & Silica Gel',
        icon: Truck,
        accentColor: 'border-rose-400 bg-rose-500/10 text-rose-700',
        badgeColor: 'bg-rose-100 text-rose-800',
        actionLabel: 'Selesaikan Pesanan',
        nextStatus: 'DELIVERED',
        actionIcon: CheckCircle2,
        btnColor: 'bg-rose-700 hover:bg-rose-800 text-white',
    },
];

const handleSearch = () => {
    router.get(
        route('workshop.index'),
        { search: searchQuery.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};

const updateStatus = (item, nextStatus, notes = null) => {
    if (!nextStatus || updatingItemId.value === item.id) return;
    if (!isActionPermitted(item.status, item)) return;

    updatingItemId.value = item.id;

    router.patch(
        route('workshop.items.update-status', item.id),
        {
            status: nextStatus,
            new_status: nextStatus,
            notes: notes || `Dipindahkan ke ${nextStatus} via Lantai Workshop`,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                updatingItemId.value = null;
                if (selectedQcItem.value?.id === item.id) {
                    selectedQcItem.value = null;
                }
            },
        }
    );
};

const openDefectModal = (item) => {
    selectedDefectItem.value = item;
};

const openQcModal = (item) => {
    selectedQcItem.value = item;
};

const getSlaBadgeClass = (slaStatus) => {
    switch (slaStatus) {
        case 'overdue':
            return 'bg-red-500 text-white animate-pulse font-bold';
        case 'urgent':
            return 'bg-amber-400 text-amber-950 font-bold';
        case 'warning':
            return 'bg-amber-100 text-amber-800 font-medium';
        default:
            return 'bg-emerald-100 text-emerald-800 font-medium';
    }
};

const stationDetails = computed(() => {
    switch (activeTab.value) {
        case 'wash':
            return {
                title: 'Stasiun Pos Cuci & Treatment',
                subtitle: 'SOP pembersihan noda, perendaman material, dan sikat upper/midsole/outsole sesuai paket',
                icon: Sparkles,
                accent: 'text-blue-700 bg-blue-50 border-blue-200',
                columns: [
                    COLUMNS.find((c) => c.key === 'CHECKED_IN'),
                    COLUMNS.find((c) => c.key === 'WASHING'),
                ],
            };
        case 'dry':
            return {
                title: 'Stasiun Pos Pengeringan (Dryer Station)',
                subtitle: 'Pengeringan suhu terkontrol dengan blower & cold dehumidifier tanpa panas berlebih',
                icon: Wind,
                accent: 'text-indigo-700 bg-indigo-50 border-indigo-200',
                column: COLUMNS.find((c) => c.key === 'DRYING'),
                items: props.grouped_items.DRYING || [],
            };
        case 'qc':
            return {
                title: 'Stasiun Quality Control (QC Inspection)',
                subtitle: 'Verifikasi kelayakan 6 parameter mutu Spatoe sebelum sepatu dipacking rapi',
                icon: ShieldCheck,
                accent: 'text-emerald-700 bg-emerald-50 border-emerald-200',
                column: COLUMNS.find((c) => c.key === 'QC_INSPECTION'),
                items: props.grouped_items.QC_INSPECTION || [],
            };
        case 'ready':
            return {
                title: 'Stasiun Siap Antar & Serah Terima',
                subtitle: 'Sepatu telah lolos QC dan dipacking rapi, siap diserahkan ke kurir pengantaran atau diambil langsung',
                icon: Truck,
                accent: 'text-rose-700 bg-rose-50 border-rose-200',
                column: COLUMNS.find((c) => c.key === 'READY_DELIVERY'),
                items: props.grouped_items.READY_DELIVERY || [],
            };
        default:
            return null;
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Workshop Floor - Antrean Cuci" />

        <div class="min-h-screen bg-neutral-100/70 pb-16">
            <!-- Header Workshop & Mini Metrics -->
            <div class="border-b border-neutral-200 bg-white shadow-xs">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="h-3 w-3 rounded-full bg-[#6B0B23]" />
                                <h1 class="text-2xl font-black tracking-tight text-neutral-900">
                                    Workshop Floor / Antrean Cuci
                                </h1>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500">
                                Pantau SLA pengerjaan, manajemen stasiun spesialisasi teknisi, dan perbarui tahapan 1-klik.
                            </p>
                        </div>

                        <!-- Mini Metrics Banner & Workstation Duty Indicator -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Workstation Duty Indicator -->
                            <div class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs shadow-2xs">
                                <span class="text-[11px] font-bold text-neutral-500">Tugas Anda:</span>
                                <span class="font-extrabold text-neutral-800">
                                    {{ getStationBadgeLabel(currentUser?.assigned_station) }}
                                </span>
                                <span
                                    v-if="isAdmin"
                                    class="rounded bg-[#6B0B23]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-[#6B0B23]"
                                >
                                    {{ currentUser?.role === 'supervisor' ? 'Supervisor' : 'Admin' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2">
                                <div class="rounded-lg bg-neutral-200 p-2 text-neutral-700">
                                    <Package class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Total Workshop</p>
                                    <p class="text-base font-extrabold text-neutral-900">{{ metrics.total_active }} <span class="text-xs font-normal text-neutral-500">pasang</span></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/50 px-3.5 py-2">
                                <div class="rounded-lg bg-amber-100 p-2 text-amber-700">
                                    <Sparkles class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Antrean Cuci</p>
                                    <p class="text-base font-extrabold text-amber-900">{{ metrics.needs_washing }} <span class="text-xs font-normal text-amber-700">pasang</span></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/50 px-3.5 py-2">
                                <div class="rounded-lg bg-rose-100 p-2 text-rose-700">
                                    <Clock class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Mendesak</p>
                                    <p class="text-base font-extrabold text-rose-900">{{ metrics.urgent_count }} <span class="text-xs font-normal text-rose-700">pasang</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Bar & Intake Kasir -->
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <form @submit.prevent="handleSearch" class="relative max-w-md flex-1">
                            <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari tag fisik (e.g. TAG-001) atau model sepatu..."
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2 pl-10 pr-9 text-xs text-neutral-800 placeholder-neutral-400 focus:border-[#6B0B23] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="clearSearch"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </form>

                        <div class="flex items-center gap-2.5">
                            <!-- Tombol Atur Shift Staf (Admin & Supervisor Only) -->
                            <button
                                v-if="isAdmin"
                                type="button"
                                @click="stationModalOpen = true"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-xs font-bold text-neutral-700 shadow-xs transition hover:bg-neutral-50 hover:text-neutral-900"
                            >
                                <Users class="h-4 w-4 text-[#6B0B23]" />
                                <span>Atur Shift Staf</span>
                            </button>

                            <Link
                                :href="route('workshop.checkin')"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-[#6B0B23] px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-[#54081B]"
                            >
                                <Plus class="h-4 w-4" />
                                <span>Intake Kasir Baru</span>
                            </Link>

                            <div class="hidden text-xs text-neutral-500 sm:block">
                                <span class="font-bold text-neutral-800">{{ items.length }}</span> aktif
                            </div>
                        </div>
                    </div>

                    <!-- Filter Pos Kerja di Atas (Assembly Line Tabs) -->
                    <div class="mt-5 border-t border-neutral-100 pt-3">
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                            <button
                                v-for="tab in WORKSTATION_TABS"
                                :key="tab.key"
                                type="button"
                                @click="activeTab = tab.key"
                                :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap shadow-2xs',
                                    activeTab === tab.key
                                        ? 'bg-[#6B0B23] text-white shadow-sm ring-2 ring-[#6B0B23]/20'
                                        : 'bg-white border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
                                ]"
                            >
                                <component :is="tab.icon" class="h-3.5 w-3.5" />
                                <span>{{ tab.label }}</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded-full text-[10px] font-extrabold',
                                        activeTab === tab.key
                                            ? 'bg-white/20 text-white'
                                            : 'bg-neutral-100 text-neutral-600'
                                    ]"
                                >
                                    {{ stationCounts[tab.key] }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View 1: Semua Pos (Overview - Kanban Board Horizontal) -->
            <div v-if="activeTab === 'all'" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                <div class="flex gap-4 overflow-x-auto pb-6 items-start snap-x">
                    <!-- Column Template -->
                    <div
                        v-for="col in COLUMNS"
                        :key="col.key"
                        class="w-72 flex-shrink-0 snap-start rounded-2xl border border-neutral-200 bg-neutral-100/90 shadow-2xs"
                    >
                        <!-- Column Header -->
                        <div class="border-b border-neutral-200/80 bg-white px-4 py-3.5 rounded-t-2xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-lg p-1.5" :class="col.accentColor">
                                        <component :is="col.icon" class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <h2 class="text-xs font-bold text-neutral-900">{{ col.title }}</h2>
                                        <p class="text-[10px] text-neutral-400">{{ col.subtitle }}</p>
                                    </div>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-xs font-extrabold" :class="col.badgeColor">
                                    {{ grouped_items[col.key]?.length || 0 }}
                                </span>
                            </div>
                        </div>

                        <!-- Card List in Column -->
                        <div class="max-h-[calc(100vh-320px)] space-y-3 overflow-y-auto p-3">
                            <div
                                v-if="!grouped_items[col.key] || grouped_items[col.key].length === 0"
                                class="rounded-xl border border-dashed border-neutral-300 py-10 text-center text-xs text-neutral-400"
                            >
                                Tidak ada sepatu di tahap ini
                            </div>

                            <!-- Shoe Card -->
                            <div
                                v-for="item in grouped_items[col.key]"
                                :key="item.id"
                                class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-xs transition-all hover:border-neutral-300 hover:shadow-sm"
                            >
                                <!-- Tag, Rak & Actions (Cetak / Defect) -->
                                <div class="flex items-start justify-between gap-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-neutral-900 px-2 py-0.5 font-mono text-[11px] font-black tracking-wider text-white">
                                            {{ item.item_tag_code }}
                                        </span>
                                        <span
                                            v-if="item.storage_shelf_number"
                                            class="rounded bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-600"
                                        >
                                            Rak {{ item.storage_shelf_number }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <!-- Thermal Print Button -->
                                        <button
                                            type="button"
                                            @click="openPrintModal(item)"
                                            class="flex items-center gap-1 rounded bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-600 hover:text-neutral-900 hover:bg-neutral-200 transition-colors"
                                            title="Cetak Struk Termal & Label Tag"
                                        >
                                            <Printer class="h-3 w-3 text-neutral-600" />
                                            <span class="hidden sm:inline">Cetak</span>
                                        </button>

                                        <!-- Defect Alert Button (if any) -->
                                        <button
                                            v-if="item.defect_notes"
                                            type="button"
                                            @click="openDefectModal(item)"
                                            class="flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 hover:bg-amber-100"
                                            title="Ada catatan noda/cacat awal"
                                        >
                                            <AlertTriangle class="h-3 w-3 text-amber-600" />
                                            <span>Defect</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Shoe Model & Service Badge -->
                                <div class="mt-2.5">
                                    <h3 class="text-sm font-bold text-neutral-900 line-clamp-1">
                                        {{ item.shoe_brand_model }}
                                    </h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-[#6B0B23] px-2 py-0.5 text-[10px] font-bold text-white">
                                            {{ item.service?.service_name || 'Layanan' }}
                                        </span>
                                        <span class="text-[11px] text-neutral-500">
                                            {{ item.shoe_material }} <span v-if="item.shoe_color">• {{ item.shoe_color }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Customer & Technician Info -->
                                <div class="mt-2.5 rounded-lg bg-neutral-50 p-2 text-[11px] text-neutral-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-neutral-400">Pemilik:</span>
                                        <span class="font-semibold text-neutral-800">{{ item.customer_name || '-' }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span class="text-neutral-400">Teknisi Pos:</span>
                                        <span class="font-medium text-neutral-700 flex items-center gap-1">
                                            <User class="h-3 w-3 text-neutral-400" />
                                            {{ item.technician?.name || 'Belum ditugaskan' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- SLA Countdown Badge -->
                                <div class="mt-2.5 flex items-center justify-between text-[11px]">
                                    <div class="flex items-center gap-1 text-neutral-400">
                                        <Clock class="h-3 w-3" />
                                        <span>SLA:</span>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px]"
                                        :class="getSlaBadgeClass(item.sla_status)"
                                    >
                                        {{ item.sla_text }}
                                    </span>
                                </div>

                                <!-- Action Buttons with Workstation Guard -->
                                <div class="mt-3.5 border-t border-neutral-100 pt-2.5">
                                    <template v-if="col.key === 'QC_INSPECTION'">
                                        <button
                                            type="button"
                                            @click="canOperateStation(col.key) && openQcModal(item)"
                                            :disabled="updatingItemId === item.id || !canOperateStation(col.key)"
                                            :class="[
                                                'flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold shadow-xs transition',
                                                !canOperateStation(col.key)
                                                    ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                                    : 'bg-emerald-700 hover:bg-emerald-800 text-white'
                                            ]"
                                        >
                                            <Lock v-if="!canOperateStation(col.key)" class="h-3.5 w-3.5" />
                                            <ShieldCheck v-else class="h-3.5 w-3.5" />
                                            <span>{{ !canOperateStation(col.key) ? 'Hanya Petugas Pos Terkait' : 'Buka Lembar QC' }}</span>
                                        </button>
                                    </template>
                                    <template v-else-if="col.actionLabel">
                                        <button
                                            type="button"
                                            @click="canOperateStation(col.key) && updateStatus(item, col.nextStatus)"
                                            :disabled="updatingItemId === item.id || !canOperateStation(col.key)"
                                            :class="[
                                                'flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold shadow-xs transition',
                                                !canOperateStation(col.key)
                                                    ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                                    : col.btnColor
                                            ]"
                                        >
                                            <Lock v-if="!canOperateStation(col.key)" class="h-3.5 w-3.5" />
                                            <component v-else :is="col.actionIcon" class="h-3.5 w-3.5" />
                                            <span>{{ !canOperateStation(col.key) ? 'Hanya Petugas Pos Terkait' : (updatingItemId === item.id ? 'Memproses...' : col.actionLabel) }}</span>
                                        </button>
                                    </template>

                                    <!-- Label Mode Pantau jika di luar jobdesk -->
                                    <div
                                        v-if="!canOperateStation(col.key)"
                                        class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
                                    >
                                        <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                                        <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View 2: Pos Cuci Spesialisasi (CHECKED_IN & WASHING Side-by-Side) -->
            <div v-else-if="activeTab === 'wash'" class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8 space-y-6">
                <!-- Station Banner -->
                <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="rounded-xl bg-blue-500/10 p-3 text-blue-700 border border-blue-200">
                            <Sparkles class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-neutral-900">Stasiun Pos Cuci & Treatment</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                Fokus penanganan noda, perendaman material, dan sikat upper/midsole/outsole sesuai SOP Spatoe.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-xl bg-blue-100 text-blue-800 px-3 py-1.5 text-xs font-bold">
                            {{ stationCounts.wash }} pasang di pos ini
                        </span>
                    </div>
                </div>

                <!-- 2 Kolom Pos Cuci: Antrean Masuk vs Sedang Dicuci (Ukuran Seimbang) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div
                        v-for="col in stationDetails.columns"
                        :key="col.key"
                        class="rounded-2xl border border-neutral-200 bg-neutral-100/90 shadow-2xs w-full"
                    >
                        <div class="border-b border-neutral-200/80 bg-white px-5 py-4 rounded-t-2xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="rounded-lg p-1.5" :class="col.accentColor">
                                    <component :is="col.icon" class="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-neutral-900">{{ col.title }}</h3>
                                    <p class="text-[10px] text-neutral-400">{{ col.subtitle }}</p>
                                </div>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold" :class="col.badgeColor">
                                {{ grouped_items[col.key]?.length || 0 }}
                            </span>
                        </div>

                        <div class="p-4 space-y-3.5 max-h-[calc(100vh-340px)] overflow-y-auto">
                            <div
                                v-if="!grouped_items[col.key] || grouped_items[col.key].length === 0"
                                class="rounded-xl border border-dashed border-neutral-300 py-12 text-center text-xs text-neutral-400 bg-white/50"
                            >
                                Tidak ada sepatu di antrean ini
                            </div>

                            <div
                                v-for="item in grouped_items[col.key]"
                                :key="item.id"
                                class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs transition-all hover:border-neutral-300 hover:shadow-sm w-full min-w-[320px] sm:min-w-[340px]"
                            >
                                <div class="flex items-start justify-between gap-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-neutral-900 px-2.5 py-0.5 font-mono text-xs font-black tracking-wider text-white">
                                            {{ item.item_tag_code }}
                                        </span>
                                        <span
                                            v-if="item.storage_shelf_number"
                                            class="rounded bg-neutral-100 border border-neutral-200 px-2 py-0.5 text-[10px] font-semibold text-neutral-600"
                                        >
                                            Rak {{ item.storage_shelf_number }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPrintModal(item)"
                                            class="flex items-center gap-1 rounded bg-neutral-100 border border-neutral-200 px-2 py-1 text-[10px] font-semibold text-neutral-600 hover:text-neutral-900 hover:bg-neutral-200 transition-colors"
                                            title="Cetak Struk Termal & Label Tag"
                                        >
                                            <Printer class="h-3 w-3 text-neutral-600" />
                                            <span>Cetak</span>
                                        </button>

                                        <button
                                            v-if="item.defect_notes"
                                            type="button"
                                            @click="openDefectModal(item)"
                                            class="flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-2 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-100"
                                            title="Ada catatan noda/cacat awal"
                                        >
                                            <AlertTriangle class="h-3 w-3 text-amber-600" />
                                            <span>Defect</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <h4 class="text-sm font-bold text-neutral-900">{{ item.shoe_brand_model }}</h4>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-[#6B0B23] px-2 py-0.5 text-[10px] font-bold text-white">
                                            {{ item.service?.service_name || 'Layanan' }}
                                        </span>
                                        <span class="text-[11px] text-neutral-500">
                                            {{ item.shoe_material }} <span v-if="item.shoe_color">• {{ item.shoe_color }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3 rounded-lg bg-neutral-50 p-2.5 text-xs text-neutral-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-neutral-400">Pemilik:</span>
                                        <span class="font-semibold text-neutral-800">{{ item.customer_name || '-' }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span class="text-neutral-400">Teknisi Pos:</span>
                                        <span class="font-medium text-neutral-700 flex items-center gap-1">
                                            <User class="h-3 w-3 text-neutral-400" />
                                            {{ item.technician?.name || 'Belum ditugaskan' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1 text-neutral-400">
                                        <Clock class="h-3 w-3" />
                                        <span>Target SLA:</span>
                                    </div>
                                    <span class="rounded-full px-2.5 py-0.5 text-[10px]" :class="getSlaBadgeClass(item.sla_status)">
                                        {{ item.sla_text }}
                                    </span>
                                </div>

                                <!-- Action Button with Guard -->
                                <div class="mt-3.5 border-t border-neutral-100 pt-3">
                                    <button
                                        type="button"
                                        @click="canOperateStation(col.key) && updateStatus(item, col.nextStatus)"
                                        :disabled="updatingItemId === item.id || !canOperateStation(col.key)"
                                        :class="[
                                            'flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold shadow-xs transition',
                                            !canOperateStation(col.key)
                                                ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                                : col.btnColor
                                        ]"
                                    >
                                        <Lock v-if="!canOperateStation(col.key)" class="h-4 w-4" />
                                        <component v-else :is="col.actionIcon" class="h-4 w-4" />
                                        <span>{{ !canOperateStation(col.key) ? 'Hanya Petugas Pos Terkait' : (updatingItemId === item.id ? 'Memproses...' : col.actionLabel) }}</span>
                                    </button>

                                    <!-- Label Mode Pantau jika bukan petugas pos cuci -->
                                    <div
                                        v-if="!canOperateStation(col.key)"
                                        class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
                                    >
                                        <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                                        <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View 3: Single Station Focused View (Pos Pengeringan, Pos QC, atau Siap Antar) -->
            <!-- Bar stasiun setara Pos Cuci (max-w-5xl), kartu di bawahnya tetap terpusat di tengah (max-w-2xl) -->
            <div v-else class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8 space-y-6">
                <!-- Station Banner (Lebar setara Pos Cuci: max-w-5xl) -->
                <div class="rounded-2xl border bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4" :class="stationDetails.accent">
                    <div class="flex items-center gap-3.5">
                        <div class="rounded-xl p-3 border bg-white/80">
                            <component :is="stationDetails.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-neutral-900">{{ stationDetails.title }}</h2>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ stationDetails.subtitle }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-xl bg-white/90 border px-3 py-1.5 text-xs font-bold text-neutral-800 shadow-2xs">
                            {{ stationDetails.items.length }} pasang di stasiun ini
                        </span>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="stationDetails.items.length === 0"
                    class="rounded-2xl border border-dashed border-neutral-300 py-16 text-center text-xs text-neutral-400 bg-white shadow-2xs max-w-2xl mx-auto"
                >
                    <component :is="stationDetails.icon" class="h-8 w-8 mx-auto text-neutral-300 mb-2" />
                    <p class="text-sm font-bold text-neutral-700">Tidak ada antrean di stasiun ini</p>
                    <p class="text-xs text-neutral-400 mt-1 max-w-sm mx-auto">
                        Semua sepatu pada tahap ini telah selesai diproses dan diteruskan ke stasiun kerja berikutnya.
                    </p>
                </div>

                <!-- Daftar Kartu Sepatu 1 di Tengah (Single Centered Column, max-w-2xl tidak dipanjangkan) -->
                <div v-else class="max-w-2xl mx-auto space-y-4 w-full">
                    <div
                        v-for="item in stationDetails.items"
                        :key="item.id"
                        class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5 shadow-xs transition-all hover:border-neutral-300 hover:shadow-sm flex flex-col justify-between w-full"
                    >
                        <div>
                            <!-- Header Card: Tag & Rak & Print/Defect -->
                            <div class="flex items-start justify-between gap-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-neutral-900 px-2.5 py-0.5 font-mono text-xs font-black tracking-wider text-white">
                                        {{ item.item_tag_code }}
                                    </span>
                                    <span
                                        v-if="item.storage_shelf_number"
                                        class="rounded bg-neutral-100 border border-neutral-200 px-2 py-0.5 text-[10px] font-semibold text-neutral-600"
                                    >
                                        Rak {{ item.storage_shelf_number }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openPrintModal(item)"
                                        class="flex items-center gap-1 rounded bg-neutral-100 border border-neutral-200 px-2 py-1 text-[10px] font-semibold text-neutral-600 hover:text-neutral-900 hover:bg-neutral-200 transition-colors"
                                        title="Cetak Struk Termal & Label Tag"
                                    >
                                        <Printer class="h-3 w-3 text-neutral-600" />
                                        <span>Cetak</span>
                                    </button>

                                    <button
                                        v-if="item.defect_notes"
                                        type="button"
                                        @click="openDefectModal(item)"
                                        class="flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-2 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-100"
                                        title="Ada catatan noda/cacat awal"
                                    >
                                        <AlertTriangle class="h-3 w-3 text-amber-600" />
                                        <span>Defect</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Shoe Model & Service Badge -->
                            <div class="mt-3">
                                <h4 class="text-sm font-bold text-neutral-900 line-clamp-1">{{ item.shoe_brand_model }}</h4>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-[#6B0B23] px-2 py-0.5 text-[10px] font-bold text-white">
                                        {{ item.service?.service_name || 'Layanan' }}
                                    </span>
                                    <span class="text-[11px] text-neutral-500">
                                        {{ item.shoe_material }} <span v-if="item.shoe_color">• {{ item.shoe_color }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Customer & Technician Info -->
                            <div class="mt-3 rounded-xl bg-neutral-50 p-2.5 text-xs text-neutral-600">
                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-400">Pemilik:</span>
                                    <span class="font-semibold text-neutral-800">{{ item.customer_name || '-' }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="text-neutral-400">Teknisi Pos:</span>
                                    <span class="font-medium text-neutral-700 flex items-center gap-1">
                                        <User class="h-3 w-3 text-neutral-400" />
                                        {{ item.technician?.name || 'Belum ditugaskan' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Target SLA -->
                            <div class="mt-3 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-1 text-neutral-400">
                                    <Clock class="h-3 w-3" />
                                    <span>Target SLA:</span>
                                </div>
                                <span class="rounded-full px-2.5 py-0.5 text-[10px]" :class="getSlaBadgeClass(item.sla_status)">
                                    {{ item.sla_text }}
                                </span>
                            </div>
                        </div>

                        <!-- 1-Klik Action Button Transition with Workstation Guard -->
                        <div class="mt-4 border-t border-neutral-100 pt-3">
                            <!-- Pos QC: Buka Lembar QC Modal -->
                            <template v-if="activeTab === 'qc'">
                                <button
                                    type="button"
                                    @click="canOperateStation('QC_INSPECTION') && openQcModal(item)"
                                    :disabled="updatingItemId === item.id || !canOperateStation('QC_INSPECTION')"
                                    :class="[
                                        'flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold shadow-xs transition',
                                        !canOperateStation('QC_INSPECTION')
                                            ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                            : 'bg-emerald-700 hover:bg-emerald-800 text-white'
                                    ]"
                                >
                                    <Lock v-if="!canOperateStation('QC_INSPECTION')" class="h-4 w-4" />
                                    <ShieldCheck v-else class="h-4 w-4" />
                                    <span>{{ !canOperateStation('QC_INSPECTION') ? 'Hanya Petugas Pos Terkait' : 'Buka Lembar QC' }}</span>
                                </button>
                                <div
                                    v-if="!canOperateStation('QC_INSPECTION')"
                                    class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
                                >
                                    <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                                    <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
                                </div>
                            </template>

                            <!-- Pos Dryer: Kirim ke Pos QC -->
                            <template v-else-if="activeTab === 'dry'">
                                <button
                                    type="button"
                                    @click="canOperateStation('DRYING') && updateStatus(item, 'QC_INSPECTION')"
                                    :disabled="updatingItemId === item.id || !canOperateStation('DRYING')"
                                    :class="[
                                        'flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold shadow-xs transition',
                                        !canOperateStation('DRYING')
                                            ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                            : 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                    ]"
                                >
                                    <Lock v-if="!canOperateStation('DRYING')" class="h-4 w-4" />
                                    <ShieldCheck v-else class="h-4 w-4" />
                                    <span>{{ !canOperateStation('DRYING') ? 'Hanya Petugas Pos Terkait' : (updatingItemId === item.id ? 'Memproses...' : 'Kirim ke Pos QC') }}</span>
                                </button>
                                <div
                                    v-if="!canOperateStation('DRYING')"
                                    class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
                                >
                                    <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                                    <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
                                </div>
                            </template>

                            <!-- Pos Siap Antar: Selesaikan Pesanan -->
                            <template v-else-if="activeTab === 'ready'">
                                <button
                                    type="button"
                                    @click="canOperateStation('READY_DELIVERY') && updateStatus(item, 'DELIVERED', 'Pesanan selesai & diserahkan')"
                                    :disabled="updatingItemId === item.id || !canOperateStation('READY_DELIVERY')"
                                    :class="[
                                        'flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold shadow-xs transition',
                                        !canOperateStation('READY_DELIVERY')
                                            ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                            : 'bg-rose-700 hover:bg-rose-800 text-white'
                                    ]"
                                >
                                    <Lock v-if="!canOperateStation('READY_DELIVERY')" class="h-4 w-4" />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    <span>{{ !canOperateStation('READY_DELIVERY') ? 'Hanya Petugas Pos Terkait' : (updatingItemId === item.id ? 'Memproses...' : 'Selesaikan Pesanan') }}</span>
                                </button>
                                <div
                                    v-if="!canOperateStation('READY_DELIVERY')"
                                    class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
                                >
                                    <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                                    <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Defect Notes & Photo Modal -->
        <div
            v-if="selectedDefectItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
            @click.self="selectedDefectItem = null"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <div class="flex items-center gap-2 text-amber-700">
                        <AlertTriangle class="h-5 w-5" />
                        <h3 class="text-base font-bold text-neutral-900">Catatan Kerusakan / Cacat Awal</h3>
                    </div>
                    <button
                        type="button"
                        @click="selectedDefectItem = null"
                        class="text-neutral-400 hover:text-neutral-600"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <span class="font-mono text-xs font-bold text-neutral-500">{{ selectedDefectItem.item_tag_code }}</span>
                        <h4 class="text-sm font-bold text-neutral-900">{{ selectedDefectItem.shoe_brand_model }}</h4>
                    </div>

                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-3.5 text-xs text-amber-900 leading-relaxed">
                        <p class="font-semibold text-amber-800 mb-1">Catatan dari Intake / Kasir:</p>
                        <p>{{ selectedDefectItem.defect_notes }}</p>
                    </div>

                    <!-- Defect Photos (if any) -->
                    <div v-if="selectedDefectItem.photos && selectedDefectItem.photos.length > 0" class="mt-3">
                        <p class="text-xs font-semibold text-neutral-700 mb-2">Foto Dokumentasi:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div
                                v-for="photo in selectedDefectItem.photos"
                                :key="photo.id"
                                class="relative aspect-video overflow-hidden rounded-lg border bg-neutral-100"
                            >
                                <img :src="photo.url" class="h-full w-full object-cover" alt="Foto Defect" />
                                <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[9px] font-bold text-white">
                                    {{ photo.photo_type }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button
                        type="button"
                        @click="selectedDefectItem = null"
                        class="w-full rounded-xl bg-neutral-900 py-2.5 text-xs font-bold text-white hover:bg-neutral-800"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- QC Inspection Modal with Guard -->
        <div
            v-if="selectedQcItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
            @click.self="selectedQcItem = null"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <div class="flex items-center gap-2 text-emerald-800">
                        <ShieldCheck class="h-5 w-5 text-emerald-600" />
                        <h3 class="text-base font-bold text-neutral-900">Lembar Verifikasi Quality Control (QC)</h3>
                    </div>
                    <button
                        type="button"
                        @click="selectedQcItem = null"
                        class="text-neutral-400 hover:text-neutral-600"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-neutral-50 p-3">
                        <div>
                            <span class="font-mono text-xs font-bold text-neutral-500">{{ selectedQcItem.item_tag_code }}</span>
                            <h4 class="text-sm font-bold text-neutral-900">{{ selectedQcItem.shoe_brand_model }}</h4>
                            <p class="text-xs text-neutral-500">{{ selectedQcItem.service?.service_name }}</p>
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-bold"
                            :class="selectedQcItem.qc?.qc_passed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        >
                            {{ selectedQcItem.qc?.qc_passed ? 'Lolos QC' : 'Perlu Verifikasi' }}
                        </span>
                    </div>

                    <!-- 6 QC Checklist Summary -->
                    <div class="rounded-xl border border-neutral-200 p-4 space-y-2.5 text-xs text-neutral-700">
                        <p class="font-bold text-neutral-900 mb-2">Checklist 6 Parameter Kelayakan Spatoe:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Upper Bersih Bebas Noda</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Midsole Bersih Terawat</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Outsole Disikat Rapi</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Kering Sempurna 100%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Disemprot Parfum Sepatu</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                <span>Ziplock & Silica Gel Terpasang</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action in QC Modal with Workstation Guard -->
                <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                    <!-- Reject / Return to Wash -->
                    <button
                        type="button"
                        @click="canOperateStation('QC_INSPECTION') && updateStatus(selectedQcItem, 'WASHING', 'QC Gagal: Sepatu dikembalikan ke tahap cuci ulang')"
                        :disabled="updatingItemId === selectedQcItem.id || !canOperateStation('QC_INSPECTION')"
                        :class="[
                            'flex flex-1 items-center justify-center gap-1.5 rounded-xl border py-2.5 text-xs font-bold transition',
                            !canOperateStation('QC_INSPECTION')
                                ? 'border-neutral-300 bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                : 'border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100'
                        ]"
                    >
                        <Lock v-if="!canOperateStation('QC_INSPECTION')" class="h-4 w-4" />
                        <RotateCcw v-else class="h-4 w-4" />
                        <span>{{ !canOperateStation('QC_INSPECTION') ? 'Hanya Petugas Pos Terkait' : 'Tolak & Cuci Ulang' }}</span>
                    </button>

                    <!-- Pass QC & Set Ready -->
                    <button
                        type="button"
                        @click="canOperateStation('QC_INSPECTION') && updateStatus(selectedQcItem, 'READY_DELIVERY', 'QC Passed: Sepatu siap diantar/diambil')"
                        :disabled="updatingItemId === selectedQcItem.id || !canOperateStation('QC_INSPECTION')"
                        :class="[
                            'flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs font-bold text-white transition',
                            !canOperateStation('QC_INSPECTION')
                                ? 'bg-neutral-300 text-neutral-500 cursor-not-allowed opacity-60'
                                : 'bg-emerald-700 hover:bg-emerald-800'
                        ]"
                    >
                        <Lock v-if="!canOperateStation('QC_INSPECTION')" class="h-4 w-4" />
                        <Check v-else class="h-4 w-4" />
                        <span>{{ !canOperateStation('QC_INSPECTION') ? 'Hanya Petugas Pos Terkait' : 'Lulus QC & Siap Antar' }}</span>
                    </button>
                </div>

                <div
                    v-if="!canOperateStation('QC_INSPECTION')"
                    class="mt-2.5 text-center text-xs text-amber-700 bg-amber-50 p-2 rounded-xl border border-amber-200/60 flex items-center justify-center gap-1.5"
                >
                    <Lock class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                    <span>Mode Pantau: Anda hanya dapat memverifikasi lembar QC jika bertugas di Pos QC atau sebagai Admin.</span>
                </div>
            </div>
        </div>

        <!-- Modal Cetak Thermal & Label Tag Rak -->
        <ThermalPrintModal
            :is-open="printModalOpen"
            :order="printOrder"
            @close="printModalOpen = false"
        />

        <!-- Modal Penugasan Stasiun Staf Mingguan (Admin Only) -->
        <StationAssignmentModal
            :is-open="stationModalOpen"
            :staff-list="staffList?.length ? staffList : (staff_list || [])"
            @close="stationModalOpen = false"
        />
    </AuthenticatedLayout>
</template>
