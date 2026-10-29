<script setup>
import { ref, computed, watch } from 'vue';
import {
    ShieldCheck,
    CheckCircle2,
    Circle,
    RotateCcw,
    Check,
    X,
    Lock,
    AlertTriangle,
    Loader2,
    CheckCheck,
    Sparkles,
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
    canOperate: {
        type: Boolean,
        default: true,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'pass', 'reject']);

// 6 Parameter SOP Mutu Spatoe
const DEFAULT_PARAMETERS = [
    {
        id: 'upper',
        label: 'Upper Bersih Bebas Noda',
        desc: 'Bebas debu, lumpur, noda bandel, dan material tidak luntur',
        checked: false,
    },
    {
        id: 'midsole',
        label: 'Midsole Bersih Terawat',
        desc: 'Bebas noda aspal, tanah, dan sisa sabun cuci',
        checked: false,
    },
    {
        id: 'outsole',
        label: 'Outsole Disikat Rapi',
        desc: 'Alur tapak disikat bersih dari kerikil dan kotoran',
        checked: false,
    },
    {
        id: 'dry',
        label: 'Kering Sempurna 100%',
        desc: 'Kering menyeluruh dengan suhu terkontrol tanpa sisa lembap',
        checked: false,
    },
    {
        id: 'perfume',
        label: 'Disemprot Parfum Sepatu',
        desc: 'Aroma segar anti-bakteri merata ke insole & lining dalam',
        checked: false,
    },
    {
        id: 'packing',
        label: 'Ziplock & Silica Gel Terpasang',
        desc: 'Kemasan bersih tersegel rapi dengan silica gel pelindung',
        checked: false,
    },
];

const parameters = ref(DEFAULT_PARAMETERS.map((p) => ({ ...p })));
const isRejectMode = ref(false);
const rejectNotes = ref('');
const rejectError = ref('');

// Reset setiap kali modal dibuka atau item berganti
const resetModalState = () => {
    parameters.value = DEFAULT_PARAMETERS.map((p) => ({ ...p, checked: false }));
    isRejectMode.value = false;
    rejectNotes.value = '';
    rejectError.value = '';
};

watch(
    () => [props.isOpen, props.item],
    ([newIsOpen]) => {
        if (newIsOpen) {
            resetModalState();
        }
    },
    { immediate: true }
);

const toggleParameter = (index) => {
    if (!props.canOperate || props.isProcessing) return;
    parameters.value[index].checked = !parameters.value[index].checked;
};

const checkedCount = computed(() => {
    return parameters.value.filter((p) => p.checked).length;
});

const isAllChecked = computed(() => {
    return checkedCount.value === 6;
});

const toggleCheckAll = () => {
    if (!props.canOperate || props.isProcessing) return;
    const shouldCheckAll = !isAllChecked.value;
    parameters.value.forEach((p) => {
        p.checked = shouldCheckAll;
    });
};

const handlePass = () => {
    if (!isAllChecked.value || !props.canOperate || props.isProcessing) return;
    emit('pass', {
        item: props.item,
        notes: 'QC Lulus: 6/6 Parameter Kelayakan Mutu Spatoe Terpenuhi',
    });
};

const openRejectMode = () => {
    if (!props.canOperate || props.isProcessing) return;
    isRejectMode.value = true;
    rejectError.value = '';
};

const cancelRejectMode = () => {
    isRejectMode.value = false;
    rejectNotes.value = '';
    rejectError.value = '';
};

const handleConfirmReject = () => {
    if (!props.canOperate || props.isProcessing) return;
    const trimmedNotes = rejectNotes.value.trim();
    if (!trimmedNotes) {
        rejectError.value = 'Catatan penolakan wajib diisi untuk mengarahkan bagian mana yang harus dicuci ulang.';
        return;
    }

    emit('reject', {
        item: props.item,
        notes: `QC Gagal: ${trimmedNotes}`,
    });
};
</script>

<template>
    <div
        v-if="isOpen && item"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs transition-opacity"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[92vh] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-neutral-100 px-6 py-4 bg-emerald-50/60">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-600/10 p-2.5 text-emerald-700">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-neutral-900">
                            Lembar Verifikasi Quality Control (QC)
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Pemeriksaan 6 parameter kelayakan standar bengkel Spatoe sebelum diserahkan ke pelanggan.
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

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Shoe Info Banner -->
                <div class="flex items-start justify-between rounded-xl border border-neutral-200 bg-neutral-50/80 p-3.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-neutral-900 px-2 py-0.5 font-mono text-[11px] font-black text-white">
                                {{ item.item_tag_code }}
                            </span>
                            <span
                                v-if="item.storage_shelf_number"
                                class="rounded border border-neutral-200 bg-white px-2 py-0.5 text-[10px] font-bold text-neutral-600"
                            >
                                Rak {{ item.storage_shelf_number }}
                            </span>
                        </div>
                        <h4 class="mt-1 text-sm font-extrabold text-neutral-900">
                            {{ item.shoe_brand_model }}
                        </h4>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500">
                            <span>Layanan: <strong class="text-neutral-800">{{ item.service?.service_name || 'Layanan' }}</strong></span>
                            <span>•</span>
                            <span>Pemilik: <strong class="text-neutral-800">{{ item.customer_name || '-' }}</strong></span>
                        </div>
                    </div>

                    <!-- Progress Indikator 6 Parameter -->
                    <div class="text-right">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-black transition-colors"
                            :class="isAllChecked ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        >
                            <Sparkles v-if="isAllChecked" class="h-3 w-3 text-emerald-600" />
                            <span>{{ checkedCount }} / 6 Terverifikasi</span>
                        </span>
                    </div>
                </div>

                <!-- Tampilan 1: Mode Checklist 6 Parameter SOP (Default) -->
                <div v-if="!isRejectMode" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">
                            Checklist 6 Parameter SOP Mutu Spatoe:
                        </p>
                        <button
                            type="button"
                            @click="toggleCheckAll"
                            :disabled="!canOperate || isProcessing"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 hover:underline disabled:opacity-50"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            <span>{{ isAllChecked ? 'Batalkan Semua' : 'Centang Semua (6/6)' }}</span>
                        </button>
                    </div>

                    <!-- Grid Kartu Checklist Interaktif -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="(param, index) in parameters"
                            :key="param.id"
                            @click="toggleParameter(index)"
                            :class="[
                                'relative flex items-start gap-3 rounded-xl p-3 border transition-all cursor-pointer select-none text-left',
                                param.checked
                                    ? 'bg-emerald-50/90 border-emerald-300 shadow-2xs'
                                    : 'bg-white border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50/60',
                                (!canOperate || isProcessing) && 'opacity-70 cursor-not-allowed'
                            ]"
                        >
                            <!-- Ikon Status Centang -->
                            <div class="mt-0.5 flex-shrink-0">
                                <CheckCircle2
                                    v-if="param.checked"
                                    class="h-5 w-5 text-emerald-600 fill-emerald-100 animate-in zoom-in-75 duration-150"
                                />
                                <Circle
                                    v-else
                                    class="h-5 w-5 text-neutral-300"
                                />
                            </div>

                            <!-- Label & Deskripsi -->
                            <div class="min-w-0 flex-1">
                                <h5
                                    :class="[
                                        'text-xs font-bold leading-tight',
                                        param.checked ? 'text-emerald-950' : 'text-neutral-800'
                                    ]"
                                >
                                    {{ param.label }}
                                </h5>
                                <p class="mt-0.5 text-[10px] text-neutral-500 line-clamp-2 leading-relaxed">
                                    {{ param.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tampilan 2: Mode Form Penolakan & Cuci Ulang -->
                <div v-else class="space-y-3 rounded-xl border border-rose-200 bg-rose-50/40 p-4 animate-in fade-in duration-150">
                    <div class="flex items-center gap-2 text-rose-800">
                        <AlertTriangle class="h-4 w-4 text-rose-600 flex-shrink-0" />
                        <h5 class="text-xs font-bold uppercase tracking-wider">
                            Formulir Penolakan QC & Cuci Ulang
                        </h5>
                    </div>
                    <p class="text-xs text-neutral-600">
                        Jelaskan dengan spesifik bagian sepatu yang belum bersih agar teknisi Pos Cuci mengetahui titik pengerjaan ulang:
                    </p>

                    <div>
                        <textarea
                            v-model="rejectNotes"
                            rows="3"
                            placeholder="Contoh: Midsole tumit bagian kiri masih ada noda lumpur cokelat, mohon disikat ulang dengan cleaner..."
                            class="w-full rounded-xl border border-rose-200 bg-white p-3 text-xs text-neutral-800 placeholder-neutral-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                        />
                        <p v-if="rejectError" class="mt-1 text-[11px] font-bold text-rose-600">
                            {{ rejectError }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer & Action Buttons -->
            <div class="border-t border-neutral-100 bg-neutral-50/70 p-4 sm:px-6">
                <!-- Mode Normal (Checklist) -->
                <div v-if="!isRejectMode" class="flex flex-col gap-2.5 sm:flex-row">
                    <!-- Tombol Tolak & Cuci Ulang (Buka Form Input Penolakan) -->
                    <button
                        type="button"
                        @click="openRejectMode"
                        :disabled="!canOperate || isProcessing"
                        :class="[
                            'flex flex-1 items-center justify-center gap-1.5 rounded-xl border py-2.5 text-xs font-bold transition-all',
                            !canOperate || isProcessing
                                ? 'border-neutral-300 bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                : 'border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 hover:border-rose-400'
                        ]"
                    >
                        <RotateCcw class="h-4 w-4" />
                        <span>Tolak & Cuci Ulang</span>
                    </button>

                    <!-- Tombol Lulus QC & Siap Antar (Validation Guard 6/6) -->
                    <button
                        type="button"
                        @click="handlePass"
                        :disabled="!isAllChecked || !canOperate || isProcessing"
                        :class="[
                            'flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all shadow-xs',
                            !isAllChecked || !canOperate || isProcessing
                                ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed opacity-60'
                                : 'bg-emerald-600 text-white hover:bg-emerald-700 ring-2 ring-emerald-600/20'
                        ]"
                    >
                        <Loader2 v-if="isProcessing" class="h-4 w-4 animate-spin" />
                        <Check v-else class="h-4 w-4" />
                        <span v-if="isAllChecked">Lulus QC & Siap Antar (6/6)</span>
                        <span v-else>Centang Semua ({{ checkedCount }}/6)</span>
                    </button>
                </div>

                <!-- Mode Penolakan Aktif (Textarea Terbuka) -->
                <div v-else class="flex flex-col gap-2.5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="cancelRejectMode"
                        :disabled="isProcessing"
                        class="rounded-xl border border-neutral-200 bg-white px-4 py-2 text-xs font-bold text-neutral-700 hover:bg-neutral-100 transition-colors"
                    >
                        Kembali ke Checklist
                    </button>

                    <button
                        type="button"
                        @click="handleConfirmReject"
                        :disabled="isProcessing || !rejectNotes.trim()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700 transition-colors disabled:opacity-50"
                    >
                        <Loader2 v-if="isProcessing" class="h-4 w-4 animate-spin" />
                        <RotateCcw v-else class="h-4 w-4" />
                        <span>{{ isProcessing ? 'Memproses...' : 'Konfirmasi Tolak & Cuci Ulang' }}</span>
                    </button>
                </div>

                <!-- Notifikasi Workstation Guard jika bukan petugas Pos QC -->
                <div
                    v-if="!canOperate"
                    class="mt-3 text-center text-xs text-amber-700 bg-amber-50 p-2 rounded-xl border border-amber-200/60 flex items-center justify-center gap-1.5"
                >
                    <Lock class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                    <span>Mode Pantau: Anda hanya dapat memverifikasi lembar QC jika bertugas di Pos QC atau sebagai Admin.</span>
                </div>
            </div>
        </div>
    </div>
</template>

