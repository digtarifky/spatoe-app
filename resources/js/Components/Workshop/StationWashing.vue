<script setup>
import { computed } from 'vue';
import ShoeCard from '@/Components/Workshop/ShoeCard.vue';
import {
    Sparkles,
    Wind,
    Package,
} from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    checkedInItems: {
        type: Array,
        default: null,
    },
    washingItems: {
        type: Array,
        default: null,
    },
    canOperate: {
        type: [Boolean, Function],
        default: true,
    },
    processingItemId: {
        type: [Number, String],
        default: null,
    },
    isOverview: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['advance', 'open-print', 'view-defect']);

const checkedInList = computed(() => {
    if (props.checkedInItems !== null && props.checkedInItems !== undefined) {
        return props.checkedInItems;
    }
    return (props.items || []).filter((item) => item.status === 'CHECKED_IN');
});

const washingList = computed(() => {
    if (props.washingItems !== null && props.washingItems !== undefined) {
        return props.washingItems;
    }
    return (props.items || []).filter((item) => item.status === 'WASHING');
});

const totalCount = computed(() => checkedInList.value.length + washingList.value.length);

const checkCanOperate = (statusKey) => {
    if (typeof props.canOperate === 'function') {
        return props.canOperate(statusKey);
    }
    return Boolean(props.canOperate);
};

const handleAdvanceCheckedIn = (item) => {
    emit('advance', {
        item,
        nextStatus: 'WASHING',
        notes: 'Mulai proses pencucian & treatment noda',
    });
};

const handleAdvanceWashing = (item) => {
    emit('advance', {
        item,
        nextStatus: 'DRYING',
        notes: 'Pencucian selesai, dipindahkan ke mesin pengering',
    });
};
</script>

<template>
    <!-- View Mode: Overview (Kanban Column) -->
    <div
        v-if="isOverview"
        class="w-72 sm:w-80 flex-shrink-0 snap-start rounded-2xl border border-neutral-200 bg-neutral-100/90 shadow-2xs"
    >
        <!-- Column Header -->
        <div class="border-b border-neutral-200/80 bg-white px-4 py-3.5 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg p-1.5 border border-blue-400 bg-blue-500/10 text-blue-700">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-neutral-900">Pos Cuci & Sikat</h2>
                        <p class="text-[10px] text-neutral-400">Intake & Treatment Noda</p>
                    </div>
                </div>
                <span class="rounded-full px-2 py-0.5 text-xs font-extrabold bg-blue-100 text-blue-800">
                    {{ totalCount }}
                </span>
            </div>
        </div>

        <!-- Card List with 2 Sub-antrean -->
        <div class="max-h-[calc(100vh-320px)] space-y-4 overflow-y-auto p-3">
            <!-- Sub-antrean 1: Intake / Antre -->
            <div>
                <div class="flex items-center justify-between px-1 mb-2">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-neutral-600 uppercase tracking-wider">
                        <Package class="h-3 w-3 text-amber-600" />
                        <span>Antrean Masuk</span>
                    </div>
                    <span class="rounded-full bg-amber-100 text-amber-800 px-1.5 py-0.5 text-[10px] font-extrabold">
                        {{ checkedInList.length }}
                    </span>
                </div>

                <div
                    v-if="checkedInList.length === 0"
                    class="rounded-xl border border-dashed border-neutral-300 py-6 text-center text-xs text-neutral-400 bg-white/50 mb-2"
                >
                    Antrean kasir kosong
                </div>
                <div v-else class="space-y-3 mb-2">
                    <ShoeCard
                        v-for="item in checkedInList"
                        :key="item.id"
                        :item="item"
                        :can-operate="checkCanOperate('CHECKED_IN')"
                        :is-processing="processingItemId === item.id"
                        action-label="Mulai Cuci"
                        :action-icon="Sparkles"
                        action-btn-class="bg-amber-600 hover:bg-amber-700 text-white"
                        @action="handleAdvanceCheckedIn"
                        @open-print="$emit('open-print', $event)"
                        @view-defect="$emit('view-defect', $event)"
                    />
                </div>
            </div>

            <!-- Sub-antrean 2: Washing / Sedang Dicuci -->
            <div class="border-t border-neutral-200/80 pt-3">
                <div class="flex items-center justify-between px-1 mb-2">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-neutral-600 uppercase tracking-wider">
                        <Sparkles class="h-3 w-3 text-blue-600" />
                        <span>Sedang Dicuci</span>
                    </div>
                    <span class="rounded-full bg-blue-100 text-blue-800 px-1.5 py-0.5 text-[10px] font-extrabold">
                        {{ washingList.length }}
                    </span>
                </div>

                <div
                    v-if="washingList.length === 0"
                    class="rounded-xl border border-dashed border-neutral-300 py-6 text-center text-xs text-neutral-400 bg-white/50"
                >
                    Tidak ada sepatu yang sedang disikat
                </div>
                <div v-else class="space-y-3">
                    <ShoeCard
                        v-for="item in washingList"
                        :key="item.id"
                        :item="item"
                        :can-operate="checkCanOperate('WASHING')"
                        :is-processing="processingItemId === item.id"
                        action-label="Pindah ke Dryer"
                        :action-icon="Wind"
                        action-btn-class="bg-blue-600 hover:bg-blue-700 text-white"
                        @action="handleAdvanceWashing"
                        @open-print="$emit('open-print', $event)"
                        @view-defect="$emit('view-defect', $event)"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- View Mode: Focused Station (Side-by-side 2 Columns) -->
    <div v-else class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
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
                    {{ totalCount }} pasang di pos ini
                </span>
            </div>
        </div>

        <!-- 2 Kolom Pos Cuci: Antrean Masuk vs Sedang Dicuci (Ukuran Seimbang) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <!-- Kolom 1: Checked In / Antre -->
            <div class="rounded-2xl border border-neutral-200 bg-neutral-100/90 shadow-2xs w-full">
                <div class="border-b border-neutral-200/80 bg-white px-5 py-4 rounded-t-2xl flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 border border-amber-400 bg-amber-500/10 text-amber-700">
                            <Package class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-neutral-900">Checked In / Antre</h3>
                            <p class="text-[10px] text-neutral-400">Intake Meja Kasir</p>
                        </div>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold bg-amber-100 text-amber-800">
                        {{ checkedInList.length }}
                    </span>
                </div>

                <div class="p-4 space-y-3.5 max-h-[calc(100vh-340px)] overflow-y-auto">
                    <div
                        v-if="checkedInList.length === 0"
                        class="rounded-xl border border-dashed border-neutral-300 py-12 text-center text-xs text-neutral-400 bg-white/50"
                    >
                        Tidak ada sepatu di antrean kasir
                    </div>
                    <ShoeCard
                        v-for="item in checkedInList"
                        :key="item.id"
                        :item="item"
                        :can-operate="checkCanOperate('CHECKED_IN')"
                        :is-processing="processingItemId === item.id"
                        action-label="Mulai Cuci"
                        :action-icon="Sparkles"
                        action-btn-class="bg-amber-600 hover:bg-amber-700 text-white"
                        @action="handleAdvanceCheckedIn"
                        @open-print="$emit('open-print', $event)"
                        @view-defect="$emit('view-defect', $event)"
                    />
                </div>
            </div>

            <!-- Kolom 2: Washing / Pencucian -->
            <div class="rounded-2xl border border-neutral-200 bg-neutral-100/90 shadow-2xs w-full">
                <div class="border-b border-neutral-200/80 bg-white px-5 py-4 rounded-t-2xl flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 border border-blue-400 bg-blue-500/10 text-blue-700">
                            <Sparkles class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-neutral-900">Washing / Pencucian</h3>
                            <p class="text-[10px] text-neutral-400">Treatment & Sikat Noda</p>
                        </div>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold bg-blue-100 text-blue-800">
                        {{ washingList.length }}
                    </span>
                </div>

                <div class="p-4 space-y-3.5 max-h-[calc(100vh-340px)] overflow-y-auto">
                    <div
                        v-if="washingList.length === 0"
                        class="rounded-xl border border-dashed border-neutral-300 py-12 text-center text-xs text-neutral-400 bg-white/50"
                    >
                        Tidak ada sepatu yang sedang dicuci
                    </div>
                    <ShoeCard
                        v-for="item in washingList"
                        :key="item.id"
                        :item="item"
                        :can-operate="checkCanOperate('WASHING')"
                        :is-processing="processingItemId === item.id"
                        action-label="Pindah ke Dryer"
                        :action-icon="Wind"
                        action-btn-class="bg-blue-600 hover:bg-blue-700 text-white"
                        @action="handleAdvanceWashing"
                        @open-print="$emit('open-print', $event)"
                        @view-defect="$emit('view-defect', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

