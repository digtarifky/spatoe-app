<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import {
    Search,
    X,
    PackageCheck,
    Sparkles,
    Wind,
    ShieldCheck,
    Truck,
    AlertCircle,
    ShoppingBag,
} from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const isSearching = ref(false);

// Sinkronkan input pencarian jika query URL berubah (Back/Forward browser)
watch(
    () => props.filters.search,
    (newSearch) => {
        searchQuery.value = newSearch || '';
    }
);

const handleSearch = () => {
    const query = searchQuery.value.trim();

    router.get(
        route('tracking.index'),
        query ? { search: query } : {},
        {
            replace: true,
            preserveState: true,
            preserveScroll: true,
            onStart: () => { isSearching.value = true; },
            onFinish: () => { isSearching.value = false; },
        }
    );
};

const clearSearch = () => {
    searchQuery.value = '';

    router.get(
        route('tracking.index'),
        {},
        {
            replace: true,
            preserveState: true,
            preserveScroll: true,
            onStart: () => { isSearching.value = true; },
            onFinish: () => { isSearching.value = false; },
        }
    );
};

// 5 Tahapan Stepper
const STEPS = [
    { status: 'CHECKED_IN', label: 'Diterima', icon: PackageCheck },
    { status: 'WASHING', label: 'Pencucian', icon: Sparkles },
    { status: 'DRYING', label: 'Pengeringan', icon: Wind },
    { status: 'QC_INSPECTION', label: 'Quality Control', icon: ShieldCheck },
    { status: 'READY_DELIVERY', label: 'Siap Antar', icon: Truck },
];

const getCurrentStepIndex = (currentStatus) => {
    if (currentStatus === 'DELIVERED') return STEPS.length - 1;
    const index = STEPS.findIndex((s) => s.status === currentStatus);
    if (index !== -1) return index;
    if (currentStatus === 'WAITING_PICKUP') return -1;
    return 0;
};

const getProgressPercentage = (currentStatus) => {
    const currentIndex = getCurrentStepIndex(currentStatus);
    if (currentIndex <= 0) return 0;
    return Math.round((currentIndex / (STEPS.length - 1)) * 100);
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-neutral-50 text-neutral-900 font-sans antialiased">
        <Head title="Lacak Pesanan Sepatu - Spatoe" />

        <!-- 4. Integrasi: Navbar di paling atas -->
        <Navbar />

        <!-- Main Content Area -->
        <main class="flex-1 py-8 sm:py-12">
            <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                <!-- Search Bar Section (Posisikan di bagian atas tengah max-w-xl mx-auto) -->
                <div class="max-w-xl mx-auto space-y-3">
                    <div class="text-center space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                            Lacak Progres Perawatan Sepatu
                        </h1>
                        <p class="text-xs sm:text-sm text-neutral-500">
                            Pantau tahapan cuci, pengeringan, hingga siap antar secara transparan & realtime.
                        </p>
                    </div>

                    <section class="bg-white rounded-2xl p-2 sm:p-2.5 shadow-sm border border-neutral-200">
                        <form @submit.prevent="handleSearch" class="relative flex items-center">
                            <Search class="absolute left-3.5 w-4 h-4 text-neutral-400 pointer-events-none" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Nomor WhatsApp / Kode Nota (misal: SPT-202610-001)"
                                class="w-full bg-neutral-50 border border-neutral-200 rounded-xl pl-10 pr-28 sm:pr-32 py-2.5 sm:py-3 text-xs sm:text-sm text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                            />
                            <!-- Tombol X (Clear) & Tombol Lacak -->
                            <div class="absolute right-1.5 flex items-center gap-1 sm:gap-1.5">
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    @click="clearSearch"
                                    class="p-1 rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-200/60 transition-colors"
                                    title="Hapus pencarian"
                                    aria-label="Hapus pencarian"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSearching"
                                    class="bg-[#6B0B23] hover:bg-[#54081B] text-white px-4 sm:px-5 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold disabled:opacity-50 transition-colors shadow-xs"
                                >
                                    {{ isSearching ? 'Mencari...' : 'Lacak' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Hasil Pelacakan: Ringkas & Minimalis (Hanya Stepper) -->
                <div v-if="orders.length > 0" class="space-y-6">
                    <template v-for="order in orders" :key="order.id">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-neutral-200 space-y-6"
                        >
                            <!-- Header Kartu: ● TAHAPAN PENGERJAAN & Nama Sepatu Jika > 1 Pasang -->
                            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#6B0B23]" />
                                    <h4 class="text-xs sm:text-sm font-bold text-neutral-900 uppercase tracking-wider">
                                        Tahapan Pengerjaan
                                    </h4>
                                </div>
                                <span
                                    v-if="order.items && order.items.length > 1"
                                    class="text-xs font-semibold text-neutral-600 bg-neutral-100 px-3 py-1 rounded-lg border border-neutral-200/60"
                                >
                                    {{ item.shoe_brand_model }}
                                </span>
                            </div>

                            <!-- Stepper Progress Horizontal (Mendatar Penuh) -->
                            <div class="relative w-full pt-1 pb-1">
                                <!-- Garis Penghubung Antar-Tahap (Progress Line) -->
                                <div class="absolute top-[18px] sm:top-[22px] left-[10%] right-[10%] h-1 -translate-y-1/2 bg-neutral-200 z-0 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-[#6B0B23] transition-all duration-500 ease-out rounded-full"
                                        :style="{ width: `${getProgressPercentage(item.status)}%` }"
                                    />
                                </div>

                                <!-- 5 Node Tahapan: CHECKED_IN, WASHING, DRYING, QC_INSPECTION, READY_DELIVERY -->
                                <div class="grid grid-cols-5 relative z-10">
                                    <div
                                        v-for="(step, idx) in STEPS"
                                        :key="step.status"
                                        class="flex flex-col items-center text-center px-0.5"
                                    >
                                        <div
                                            :class="[
                                                'w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center transition-all duration-500 font-bold',
                                                idx <= getCurrentStepIndex(item.status)
                                                    ? 'bg-[#6B0B23] text-white shadow-xs'
                                                    : 'bg-neutral-100 border border-neutral-200 text-neutral-400'
                                            ]"
                                        >
                                            <component :is="step.icon" class="w-4 h-4 sm:w-5 sm:h-5 text-current" />
                                        </div>
                                        <div class="mt-2.5">
                                            <p
                                                :class="[
                                                    'text-[11px] sm:text-xs tracking-tight transition-colors leading-tight',
                                                    idx <= getCurrentStepIndex(item.status)
                                                        ? 'text-neutral-900 font-bold'
                                                        : 'text-neutral-400'
                                                ]"
                                            >
                                                {{ step.label }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Kondisi: Pencarian Dilakukan Tapi Tidak Ditemukan -->
                <div
                    v-else-if="filters.search"
                    class="text-center py-16 bg-white rounded-2xl border border-neutral-200 p-8 space-y-3 max-w-2xl mx-auto shadow-sm"
                >
                    <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto">
                        <AlertCircle class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-bold text-neutral-800">Pesanan Tidak Ditemukan</h3>
                    <p class="text-xs sm:text-sm text-neutral-500 max-w-md mx-auto leading-relaxed">
                        Nomor WhatsApp atau kode nota <strong class="text-neutral-800 font-mono">"{{ filters.search }}"</strong> belum terdaftar di sistem bengkel Spatoe. Pastikan nomor atau kode nota yang Anda masukkan sudah benar.
                    </p>
                </div>

                <!-- Kondisi Default: Belum Melakukan Pencarian -->
                <div
                    v-else
                    class="text-center py-16 bg-white rounded-2xl border border-neutral-200 p-8 space-y-3 max-w-2xl mx-auto shadow-sm"
                >
                    <div class="w-14 h-14 rounded-full bg-[#6B0B23]/10 text-[#6B0B23] flex items-center justify-center mx-auto">
                        <ShoppingBag class="w-7 h-7" />
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-neutral-800">Pantau Perawatan Sepatumu</h3>
                    <p class="text-xs sm:text-sm text-neutral-500 max-w-md mx-auto leading-relaxed">
                        Masukkan nomor WhatsApp terdaftar atau kode nota yang tertera pada pesan konfirmasi untuk melihat progres perawatan secara realtime.
                    </p>
                </div>
            </div>
        </main>

        <!-- 4. Integrasi: Footer di paling bawah -->
        <Footer />
    </div>
</template>
