<script setup>
import { computed } from 'vue';
import ShoeCard from '@/Components/Workshop/ShoeCard.vue';
import { Truck, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    canOperate: {
        type: Boolean,
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

const itemCount = computed(() => props.items?.length || 0);

const handleAdvance = (item) => {
    emit('advance', {
        item,
        nextStatus: 'DELIVERED',
        notes: 'Pesanan selesai & diserahkan',
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
                    <div class="rounded-lg p-1.5 border border-rose-400 bg-rose-500/10 text-rose-700">
                        <Truck class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-neutral-900">Ready / Siap Antar</h2>
                        <p class="text-[10px] text-neutral-400">Packing & Silica Gel</p>
                    </div>
                </div>
                <span class="rounded-full px-2 py-0.5 text-xs font-extrabold bg-rose-100 text-rose-800">
                    {{ itemCount }}
                </span>
            </div>
        </div>

        <!-- Card List -->
        <div class="max-h-[calc(100vh-320px)] space-y-3 overflow-y-auto p-3">
            <div
                v-if="itemCount === 0"
                class="rounded-xl border border-dashed border-neutral-300 py-10 text-center text-xs text-neutral-400 bg-white/50"
            >
                Tidak ada sepatu di tahap ini
            </div>
            <ShoeCard
                v-for="item in items"
                :key="item.id"
                :item="item"
                :can-operate="canOperate"
                :is-processing="processingItemId === item.id"
                action-label="Selesaikan Pesanan"
                :action-icon="CheckCircle2"
                action-btn-class="bg-rose-700 hover:bg-rose-800 text-white"
                @action="handleAdvance"
                @open-print="$emit('open-print', $event)"
                @view-defect="$emit('view-defect', $event)"
            />
        </div>
    </div>

    <!-- View Mode: Focused Station (Centered Single Column) -->
    <div v-else class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Station Banner (Lebar setara Pos Cuci: max-w-5xl) -->
        <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="rounded-xl p-3 border border-rose-200 bg-rose-50 text-rose-700">
                    <Truck class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-neutral-900">Stasiun Siap Antar & Serah Terima</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">
                        Sepatu telah lolos QC dan dipacking rapi, siap diserahkan ke kurir pengantaran atau diambil langsung.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-xl bg-rose-50 border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-800 shadow-2xs">
                    {{ itemCount }} pasang di stasiun ini
                </span>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="itemCount === 0"
            class="rounded-2xl border border-dashed border-neutral-300 py-16 text-center text-xs text-neutral-400 bg-white shadow-2xs max-w-2xl mx-auto"
        >
            <Truck class="h-8 w-8 mx-auto text-neutral-300 mb-2" />
            <p class="text-sm font-bold text-neutral-700">Tidak ada antrean di stasiun ini</p>
            <p class="text-xs text-neutral-400 mt-1 max-w-sm mx-auto">
                Semua pesanan di tahap siap antar telah diserahkan ke pelanggan.
            </p>
        </div>

        <!-- Centered Card List (max-w-2xl mx-auto) -->
        <div v-else class="max-w-2xl mx-auto space-y-4 w-full">
            <ShoeCard
                v-for="item in items"
                :key="item.id"
                :item="item"
                :can-operate="canOperate"
                :is-processing="processingItemId === item.id"
                action-label="Selesaikan Pesanan"
                :action-icon="CheckCircle2"
                action-btn-class="bg-rose-700 hover:bg-rose-800 text-white"
                @action="handleAdvance"
                @open-print="$emit('open-print', $event)"
                @view-defect="$emit('view-defect', $event)"
            />
        </div>
    </div>
</template>

