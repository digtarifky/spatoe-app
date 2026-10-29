<script setup>
import { computed } from 'vue';
import {
    Printer,
    AlertTriangle,
    Clock,
    User,
    Lock,
    ShieldCheck,
    RotateCcw,
} from 'lucide-vue-next';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    canOperate: {
        type: Boolean,
        default: true,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionIcon: {
        type: [Object, Function],
        default: null,
    },
    actionBtnClass: {
        type: String,
        default: 'bg-neutral-900 hover:bg-neutral-800 text-white',
    },
    isQc: {
        type: Boolean,
        default: false,
    },
    showAction: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['action', 'open-qc', 'open-print', 'view-defect']);

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

const hasQcRejection = computed(() => {
    return props.item.qc && !props.item.qc.qc_passed && props.item.qc.qc_notes;
});
</script>

<template>
    <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-xs transition-all hover:border-neutral-300 hover:shadow-sm w-full">
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
                    @click="$emit('open-print', item)"
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
                    @click="$emit('view-defect', item)"
                    class="flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 hover:bg-amber-100"
                    title="Ada catatan noda/cacat awal"
                >
                    <AlertTriangle class="h-3 w-3 text-amber-600" />
                    <span>Defect</span>
                </button>
            </div>
        </div>

        <!-- Banner Merah QC Notes (Jika pernah ditolak saat QC) -->
        <div
            v-if="hasQcRejection"
            class="mt-2.5 rounded-lg border border-red-200 bg-red-50 p-2 text-xs text-red-800"
        >
            <div class="flex items-center gap-1.5 font-bold text-red-900 mb-0.5">
                <RotateCcw class="h-3.5 w-3.5 text-red-600 flex-shrink-0" />
                <span>Perlu Cuci Ulang (Ditolak QC):</span>
            </div>
            <p class="text-[11px] text-red-700 leading-snug">{{ item.qc.qc_notes }}</p>
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
        <div v-if="showAction" class="mt-3.5 border-t border-neutral-100 pt-2.5">
            <!-- Pos QC: Buka Lembar QC -->
            <template v-if="isQc">
                <button
                    type="button"
                    @click="canOperate && $emit('open-qc', item)"
                    :disabled="isProcessing || !canOperate"
                    :class="[
                        'flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold shadow-xs transition',
                        !canOperate
                            ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                            : 'bg-emerald-700 hover:bg-emerald-800 text-white'
                    ]"
                >
                    <Lock v-if="!canOperate" class="h-3.5 w-3.5" />
                    <ShieldCheck v-else class="h-3.5 w-3.5" />
                    <span>{{ !canOperate ? 'Hanya Petugas Pos Terkait' : 'Buka Lembar QC' }}</span>
                </button>
            </template>

            <!-- Action Button Umum -->
            <template v-else-if="actionLabel">
                <button
                    type="button"
                    @click="canOperate && $emit('action', item)"
                    :disabled="isProcessing || !canOperate"
                    :class="[
                        'flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold shadow-xs transition',
                        !canOperate
                            ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                            : actionBtnClass
                    ]"
                >
                    <Lock v-if="!canOperate" class="h-3.5 w-3.5" />
                    <component v-else-if="actionIcon" :is="actionIcon" class="h-3.5 w-3.5" />
                    <span>{{ !canOperate ? 'Hanya Petugas Pos Terkait' : (isProcessing ? 'Memproses...' : actionLabel) }}</span>
                </button>
            </template>

            <!-- Label Mode Pantau jika di luar jobdesk -->
            <div
                v-if="!canOperate"
                class="mt-1.5 flex items-center justify-center gap-1 text-[10px] text-amber-700 bg-amber-50 rounded-lg py-1 px-2 border border-amber-200/60 text-center"
            >
                <Lock class="w-3 h-3 text-amber-600 flex-shrink-0" />
                <span>Mode Pantau (Hanya petugas pos terkait yang dapat memindahkan)</span>
            </div>
        </div>
    </div>
</template>

