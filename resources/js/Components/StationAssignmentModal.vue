<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Users,
    X,
    Check,
    Loader2,
    Shield,
    Sparkles,
    Wind,
    ShieldCheck,
    Truck,
    LayoutGrid,
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    staffList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const isSubmitting = ref(false);
const formAssignments = ref([]);

const STATION_OPTIONS = [
    { value: 'ALL', label: 'Semua Pos (Rotasi Bebas / ALL)', icon: LayoutGrid, color: 'text-neutral-700' },
    { value: 'WASHING', label: 'Pos Cuci & Sikat (WASHING)', icon: Sparkles, color: 'text-blue-700' },
    { value: 'DRYING', label: 'Pos Pengeringan (DRYING)', icon: Wind, color: 'text-indigo-700' },
    { value: 'QC_INSPECTION', label: 'Pos Quality Control (QC_INSPECTION)', icon: ShieldCheck, color: 'text-emerald-700' },
    { value: 'READY_DELIVERY', label: 'Pos Siap Antar / Packing (READY_DELIVERY)', icon: Truck, color: 'text-rose-700' },
];

const syncAssignmentsFromProps = () => {
    formAssignments.value = props.staffList.map((staff) => ({
        id: staff.id,
        name: staff.name,
        role: staff.role,
        assigned_station: staff.assigned_station || 'ALL',
    }));
};

watch(
    () => [props.isOpen, props.staffList],
    () => {
        if (props.isOpen) {
            syncAssignmentsFromProps();
        }
    },
    { immediate: true, deep: true }
);

const getInitials = (name) => {
    if (!name) return 'S';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
};

const handleSave = () => {
    isSubmitting.value = true;

    const payload = {
        assignments: formAssignments.value.map((staff) => ({
            id: staff.id,
            assigned_station: staff.assigned_station,
        })),
    };

    router.post(route('workshop.station-assignments.update'), payload, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs transition-opacity"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-neutral-100 px-6 py-4 bg-neutral-50/70">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-[#6B0B23]/10 p-2.5 text-[#6B0B23]">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-neutral-900">
                            Penugasan Stasiun Staf Mingguan
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Atur pos tugas spesialisasi teknisi & admin untuk rotasi mingguan workshop Spatoe.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-200/60 hover:text-neutral-700 transition-colors"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Modal Body (Staff List Cards) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-3.5">
                <div
                    v-if="formAssignments.length === 0"
                    class="py-12 text-center text-xs text-neutral-400"
                >
                    Tidak ada staf teknisi aktif yang terdaftar.
                </div>

                <div
                    v-for="(staff, index) in formAssignments"
                    :key="staff.id"
                    class="rounded-xl border border-neutral-200 bg-white p-3.5 sm:p-4 shadow-2xs hover:border-neutral-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                    <!-- Staff Identity with Avatar Initials -->
                    <div class="flex items-center gap-3 min-w-[200px]">
                        <div class="h-10 w-10 rounded-full bg-[#6B0B23] text-white font-extrabold text-xs flex items-center justify-center tracking-wider shadow-xs flex-shrink-0">
                            {{ getInitials(staff.name) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-neutral-900">{{ staff.name }}</h4>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span
                                    :class="[
                                        'rounded px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider',
                                        staff.role === 'admin'
                                            ? 'bg-[#6B0B23]/10 text-[#6B0B23]'
                                            : 'bg-neutral-100 text-neutral-600'
                                    ]"
                                >
                                    {{ staff.role === 'admin' ? 'Administrator' : 'Teknisi' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Workstation Assignment Dropdown -->
                    <div class="flex-1 sm:max-w-xs">
                        <label class="block text-[11px] font-bold text-neutral-500 mb-1 sm:hidden">
                            Pos Tugas:
                        </label>
                        <select
                            v-model="staff.assigned_station"
                            class="w-full rounded-xl border border-neutral-200 bg-neutral-50 py-2 pl-3 pr-8 text-xs font-semibold text-neutral-800 focus:border-[#6B0B23] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 transition-all cursor-pointer"
                        >
                            <option
                                v-for="opt in STATION_OPTIONS"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between border-t border-neutral-100 px-6 py-4 bg-neutral-50/70">
                <span class="text-xs text-neutral-500">
                    <span class="font-bold text-neutral-800">{{ formAssignments.length }}</span> staf siap ditugaskan
                </span>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        :disabled="isSubmitting"
                        class="rounded-xl border border-neutral-200 bg-white px-4 py-2 text-xs font-bold text-neutral-700 hover:bg-neutral-100 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleSave"
                        :disabled="isSubmitting"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#6B0B23] px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-[#54081B] transition-colors disabled:opacity-50"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        <Check v-else class="h-4 w-4" />
                        <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Penugasan' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

