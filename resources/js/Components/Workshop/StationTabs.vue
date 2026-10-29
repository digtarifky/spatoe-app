<script setup>
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Sparkles,
    Wind,
    ShieldCheck,
    Truck,
    Users,
    Plus,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'all',
    },
    activeTab: {
        type: String,
        default: '',
    },
    stationCounts: {
        type: Object,
        default: () => ({
            all: 0,
            wash: 0,
            dry: 0,
            qc: 0,
            ready: 0,
        }),
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:activeTab', 'open-shift']);

const currentTab = () => props.activeTab || props.modelValue;

const setTab = (key) => {
    emit('update:modelValue', key);
    emit('update:activeTab', key);
};

const WORKSTATION_TABS = [
    { key: 'all', label: 'Semua Pos (Overview)', icon: LayoutGrid },
    { key: 'wash', label: 'Pos Cuci', icon: Sparkles },
    { key: 'dry', label: 'Pos Pengeringan', icon: Wind },
    { key: 'qc', label: 'Pos QC', icon: ShieldCheck },
    { key: 'ready', label: 'Siap Antar', icon: Truck },
];
</script>

<template>
    <div class="border-t border-neutral-100 pt-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <!-- Sisi Kiri: Tab Pill Filter Stasiun Kerja -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none min-w-0 flex-1">
            <button
                v-for="tab in WORKSTATION_TABS"
                :key="tab.key"
                type="button"
                @click="setTab(tab.key)"
                :class="[
                    'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap shadow-2xs flex-shrink-0',
                    currentTab() === tab.key
                        ? 'bg-[#6B0B23] text-white shadow-sm ring-2 ring-[#6B0B23]/20'
                        : 'bg-white border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
                ]"
            >
                <component :is="tab.icon" class="h-3.5 w-3.5" />
                <span>{{ tab.label }}</span>
                <span
                    :class="[
                        'px-1.5 py-0.5 rounded-full text-[10px] font-extrabold',
                        currentTab() === tab.key
                            ? 'bg-white/20 text-white'
                            : 'bg-neutral-100 text-neutral-600'
                    ]"
                >
                    {{ stationCounts[tab.key] || 0 }}
                </span>
            </button>
        </div>

        <!-- Sisi Kanan: Tombol Aksi Sejajar Horizontal di Pojok Kanan -->
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <slot name="actions">
                <!-- Tombol Atur Shift Teknisi (Admin & Supervisor Only) -->
                <button
                    v-if="isAdmin"
                    type="button"
                    @click="$emit('open-shift')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-xs font-bold text-neutral-700 shadow-2xs transition hover:bg-neutral-50 hover:text-neutral-900 whitespace-nowrap"
                >
                    <Users class="h-3.5 w-3.5 text-[#6B0B23]" />
                    <span>Atur Shift Teknisi</span>
                </button>

                <!-- Tombol + Terima Sepatu Baru -->
                <Link
                    :href="route('workshop.checkin')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#6B0B23] px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-[#54081B] whitespace-nowrap"
                >
                    <Plus class="h-3.5 w-3.5" />
                    <span>+ Terima Sepatu Baru</span>
                </Link>
            </slot>
        </div>
    </div>
</template>

