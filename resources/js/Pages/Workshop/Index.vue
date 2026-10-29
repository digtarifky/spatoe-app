<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkshopHeader from '@/Components/Workshop/WorkshopHeader.vue';
import StationTabs from '@/Components/Workshop/StationTabs.vue';
import StationWashing from '@/Components/Workshop/StationWashing.vue';
import StationDrying from '@/Components/Workshop/StationDrying.vue';
import StationQC from '@/Components/Workshop/StationQC.vue';
import StationReady from '@/Components/Workshop/StationReady.vue';
import ThermalPrintModal from '@/Components/ThermalPrintModal.vue';
import StationAssignmentModal from '@/Components/StationAssignmentModal.vue';
import QcModal from '@/Components/QcModal.vue';
import { AlertTriangle, X } from 'lucide-vue-next';

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

const handleSearch = () => {
    router.get(
        route('workshop.index'),
        { search: searchQuery.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const openPrintModal = (item) => {
    if (!isAdmin.value) return;

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

const updateStatus = (item, nextStatus, notes = null) => {
    if (!nextStatus || updatingItemId.value === item.id) return;
    if (!canOperateStation(item.status)) return;

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

const handleAdvance = ({ item, nextStatus, notes }) => {
    updateStatus(item, nextStatus, notes);
};

const openDefectModal = (item) => {
    selectedDefectItem.value = item;
};

const openQcModal = (item) => {
    selectedQcItem.value = item;
};

const handleQcPass = ({ item, notes }) => {
    updateStatus(item, 'READY_DELIVERY', notes);
};

const handleQcReject = ({ item, notes }) => {
    updateStatus(item, 'WASHING', notes);
};
</script>

<template>
    <AuthenticatedLayout
        :search="searchQuery"
        @update:search="searchQuery = $event"
        @search="handleSearch"
    >
        <Head title="Workshop Floor - Antrean Cuci" />

        <div class="min-h-screen bg-neutral-100/70 pb-16">
            <!-- Header Workshop & Toolbar Baris Terpadu -->
            <div class="border-b border-neutral-200 bg-white shadow-xs">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 space-y-4">
                    <!-- Baris 1: Judul di Kiri, 3 Kartu Metrik di Kanan Atas -->
                    <WorkshopHeader :metrics="metrics" />

                    <!-- Baris 2: Toolbar Filter Stasiun (Kiri) & Tombol Aksi (Kanan) -->
                    <StationTabs
                        v-model="activeTab"
                        :station-counts="stationCounts"
                        :is-admin="isAdmin"
                        @open-shift="stationModalOpen = true"
                    />
                </div>
            </div>

            <!-- View 1: Semua Pos (Overview - 4 Kolom Berdampingan) -->
            <div v-if="activeTab === 'all'" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                <div class="flex gap-4 overflow-x-auto pb-6 items-start snap-x">
                    <!-- Kolom 1: Pos Cuci & Sikat (Checked In & Washing) -->
                    <StationWashing
                        :items="items"
                        :checked-in-items="grouped_items.CHECKED_IN"
                        :washing-items="grouped_items.WASHING"
                        :can-operate="canOperateStation"
                        :processing-item-id="updatingItemId"
                        :is-overview="true"
                        @advance="handleAdvance"
                        @open-print="openPrintModal"
                        @view-defect="openDefectModal"
                    />

                    <!-- Kolom 2: Pos Pengeringan -->
                    <StationDrying
                        :items="grouped_items.DRYING"
                        :can-operate="canOperateStation('DRYING')"
                        :processing-item-id="updatingItemId"
                        :is-overview="true"
                        @advance="handleAdvance"
                        @open-print="openPrintModal"
                        @view-defect="openDefectModal"
                    />

                    <!-- Kolom 3: Pos QC Inspection -->
                    <StationQC
                        :items="grouped_items.QC_INSPECTION"
                        :can-operate="canOperateStation('QC_INSPECTION')"
                        :processing-item-id="updatingItemId"
                        :is-overview="true"
                        @open-qc="openQcModal"
                        @open-print="openPrintModal"
                        @view-defect="openDefectModal"
                    />

                    <!-- Kolom 4: Pos Siap Antar -->
                    <StationReady
                        :items="grouped_items.READY_DELIVERY"
                        :can-operate="canOperateStation('READY_DELIVERY')"
                        :processing-item-id="updatingItemId"
                        :is-overview="true"
                        @advance="handleAdvance"
                        @open-print="openPrintModal"
                        @view-defect="openDefectModal"
                    />
                </div>
            </div>

            <!-- View 2: Tampilan Terpusat Stasiun Spesifik (max-w-4xl / max-w-5xl) -->
            <div v-else class="pt-6">
                <!-- Stasiun Cuci Spesifik -->
                <StationWashing
                    v-if="activeTab === 'wash'"
                    :items="items"
                    :checked-in-items="grouped_items.CHECKED_IN"
                    :washing-items="grouped_items.WASHING"
                    :can-operate="canOperateStation"
                    :processing-item-id="updatingItemId"
                    :is-overview="false"
                    @advance="handleAdvance"
                    @open-print="openPrintModal"
                    @view-defect="openDefectModal"
                />

                <!-- Stasiun Pengeringan Spesifik -->
                <StationDrying
                    v-else-if="activeTab === 'dry'"
                    :items="grouped_items.DRYING"
                    :can-operate="canOperateStation('DRYING')"
                    :processing-item-id="updatingItemId"
                    :is-overview="false"
                    @advance="handleAdvance"
                    @open-print="openPrintModal"
                    @view-defect="openDefectModal"
                />

                <!-- Stasiun QC Spesifik -->
                <StationQC
                    v-else-if="activeTab === 'qc'"
                    :items="grouped_items.QC_INSPECTION"
                    :can-operate="canOperateStation('QC_INSPECTION')"
                    :processing-item-id="updatingItemId"
                    :is-overview="false"
                    @open-qc="openQcModal"
                    @open-print="openPrintModal"
                    @view-defect="openDefectModal"
                />

                <!-- Stasiun Siap Antar Spesifik -->
                <StationReady
                    v-else-if="activeTab === 'ready'"
                    :items="grouped_items.READY_DELIVERY"
                    :can-operate="canOperateStation('READY_DELIVERY')"
                    :processing-item-id="updatingItemId"
                    :is-overview="false"
                    @advance="handleAdvance"
                    @open-print="openPrintModal"
                    @view-defect="openDefectModal"
                />
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

        <!-- Modal Lembar Verifikasi QC Interaktif -->
        <QcModal
            :is-open="Boolean(selectedQcItem)"
            :item="selectedQcItem"
            :can-operate="canOperateStation('QC_INSPECTION')"
            :is-processing="updatingItemId === selectedQcItem?.id"
            @close="selectedQcItem = null"
            @pass="handleQcPass"
            @reject="handleQcReject"
        />

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
