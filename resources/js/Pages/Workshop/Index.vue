<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
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
    Check,
    ExternalLink
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
});

const searchQuery = ref(props.filters.search || '');
const updatingItemId = ref(null);
const selectedDefectItem = ref(null);
const selectedQcItem = ref(null);

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
        actionLabel: 'Kirim ke QC',
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
        actionIcon: CheckCircle2,
        btnColor: 'bg-emerald-700 hover:bg-emerald-800 text-white',
    },
    {
        key: 'READY_DELIVERY',
        title: 'Ready / Siap Antar',
        subtitle: 'Packing & Silica Gel',
        icon: Truck,
        accentColor: 'border-rose-400 bg-rose-500/10 text-rose-700',
        badgeColor: 'bg-rose-100 text-rose-800',
        actionLabel: null,
        nextStatus: null,
        actionIcon: null,
        btnColor: '',
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

    updatingItemId.value = item.id;

    router.patch(
        route('workshop.items.update-status', item.id),
        {
            new_status: nextStatus,
            notes: notes || `Dipindahkan ke ${nextStatus} via Kanban Board`,
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
                                Pantau SLA pengerjaan, manajemen alur kerja teknisi, dan perbarui tahapan cuci 1-klik.
                            </p>
                        </div>

                        <!-- Mini Metrics Banner -->
                        <div class="flex flex-wrap gap-3">
                            <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2">
                                <div class="rounded-lg bg-neutral-200 p-2 text-neutral-700">
                                    <Package class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Total di Workshop</p>
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
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Mendesak / Terlambat</p>
                                    <p class="text-base font-extrabold text-rose-900">{{ metrics.urgent_count }} <span class="text-xs font-normal text-rose-700">pasang</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Bar -->
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

                        <div class="flex items-center gap-3">
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
                </div>
            </div>

            <!-- Kanban Board Container -->
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
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
                        <div class="max-h-[calc(100vh-280px)] space-y-3 overflow-y-auto p-3">
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
                                <!-- Tag, Rak & Technician -->
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
                                        <span class="text-neutral-400">Teknisi:</span>
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

                                <!-- Action Buttons -->
                                <div class="mt-3.5 border-t border-neutral-100 pt-2.5">
                                    <!-- Button for QC inspection column -->
                                    <template v-if="col.key === 'QC_INSPECTION'">
                                        <button
                                            type="button"
                                            @click="openQcModal(item)"
                                            :disabled="updatingItemId === item.id"
                                            class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-700 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800 disabled:opacity-50"
                                        >
                                            <ShieldCheck class="h-3.5 w-3.5" />
                                            <span>Buka Lembar QC</span>
                                        </button>
                                    </template>

                                    <!-- Button for other active workflow columns -->
                                    <template v-else-if="col.actionLabel">
                                        <button
                                            type="button"
                                            @click="updateStatus(item, col.nextStatus)"
                                            :disabled="updatingItemId === item.id"
                                            class="flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold shadow-xs transition disabled:opacity-50"
                                            :class="col.btnColor"
                                        >
                                            <component :is="col.actionIcon" class="h-3.5 w-3.5" />
                                            <span>{{ updatingItemId === item.id ? 'Memproses...' : col.actionLabel }}</span>
                                        </button>
                                    </template>

                                    <!-- Status for Ready / Delivery -->
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1 text-[11px] font-semibold text-rose-700 py-1">
                                            <CheckCircle2 class="h-3.5 w-3.5 text-rose-600" />
                                            <span>Selesai diproses workshop</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
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

        <!-- QC Inspection Modal -->
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

                <!-- Action in QC Modal -->
                <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                    <!-- Reject / Return to Wash -->
                    <button
                        type="button"
                        @click="updateStatus(selectedQcItem, 'WASHING', 'QC Gagal: Sepatu dikembalikan ke tahap cuci ulang')"
                        :disabled="updatingItemId === selectedQcItem.id"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-100 disabled:opacity-50"
                    >
                        <RotateCcw class="h-4 w-4" />
                        <span>Tolak & Cuci Ulang</span>
                    </button>

                    <!-- Pass QC & Set Ready -->
                    <button
                        type="button"
                        @click="updateStatus(selectedQcItem, 'READY_DELIVERY', 'QC Passed: Sepatu siap diantar/diambil')"
                        :disabled="updatingItemId === selectedQcItem.id"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-700 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50"
                    >
                        <Check class="h-4 w-4" />
                        <span>Lulus QC & Siap Antar</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
