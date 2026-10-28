<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import {
    Search,
    CheckCircle2,
    Clock,
    Sparkles,
    Wind,
    PackageCheck,
    Truck,
    MessageCircle,
    ChevronRight,
    ShieldCheck,
    AlertCircle,
    ShoppingBag
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

// State tab foto per sepatu (format: { [itemId]: 'before' | 'after' })
const activePhotoTabs = ref({});

const setPhotoTab = (itemId, tab) => {
    activePhotoTabs.value[itemId] = tab;
};

const handleSearch = () => {
    if (!searchQuery.value.trim()) return;

    router.get(
        route('tracking.index'),
        { search: searchQuery.value },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => { isSearching.value = true; },
            onFinish: () => { isSearching.value = false; },
        }
    );
};

const STEPS = [
    { status: 'CHECKED_IN', label: 'Diterima', icon: PackageCheck },
    { status: 'WASHING', label: 'Cuci', icon: Sparkles },
    { status: 'DRYING', label: 'Pengeringan', icon: Wind },
    { status: 'QC_INSPECTION', label: 'QC Check', icon: ShieldCheck },
    { status: 'READY_DELIVERY', label: 'Siap Antar', icon: Truck },
];

const getStepIndex = (currentStatus) => {
    if (currentStatus === 'DELIVERED') return STEPS.length;
    const index = STEPS.findIndex((s) => s.status === currentStatus);
    return index !== -1 ? index : 0;
};

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-neutral-50 text-neutral-900 font-sans antialiased">
        <Head title="Lacak Pesanan Sepatu - Spatoe" />

        <!-- Navigation Bar -->
        <Navbar />

        <!-- Main Content Area -->
        <main class="flex-1 pb-16">
            <div class="max-w-md mx-auto px-4 mt-6 space-y-5">
                <!-- Search Input Box -->
                <section class="bg-white rounded-2xl p-4 shadow-sm border border-neutral-100">
                    <form @submit.prevent="handleSearch" class="relative flex items-center">
                        <Search class="absolute left-3.5 w-4 h-4 text-neutral-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Nomor WA / Kode Nota (e.g., SPT-202610-001)"
                            class="w-full bg-neutral-50 border border-neutral-200 rounded-xl pl-10 pr-24 py-2.5 text-xs text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                        />
                        <button
                            type="submit"
                            :disabled="isSearching"
                            class="absolute right-1.5 bg-[#6B0B23] text-white px-4 py-1.5 rounded-lg text-xs font-semibold hover:bg-[#58091c] disabled:opacity-50 transition-colors"
                        >
                            {{ isSearching ? 'Mencari...' : 'Lacak' }}
                        </button>
                    </form>
                </section>

                <!-- Tampilan Jika Ada Hasil Pencarian -->
                <div v-if="orders.length > 0" class="space-y-6">
                    <article
                        v-for="order in orders"
                        :key="order.id"
                        class="space-y-4"
                    >
                        <!-- Kartu Ringkasan Nota -->
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-neutral-100 space-y-3">
                            <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Kode Nota</span>
                                    <h2 class="text-base font-extrabold text-neutral-900">{{ order.order_code }}</h2>
                                </div>
                                <div class="text-right">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold',
                                            order.payment_status === 'PAID'
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                : 'bg-amber-50 text-amber-700 border border-amber-200'
                                        ]"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        {{ order.payment_status === 'PAID' ? 'Lunas' : 'Belum Lunas' }}
                                    </span>
                                    <p class="text-xs font-bold text-neutral-800 mt-1">
                                        {{ formatRupiah(order.final_amount) }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-xs text-neutral-600 space-y-1">
                                <p class="font-bold text-neutral-800">{{ order.customer_name }}</p>
                                <p class="text-neutral-500 line-clamp-1">{{ order.delivery_address }}</p>
                                <p class="text-[10px] text-neutral-400">Diterima: {{ order.created_at }}</p>
                            </div>
                        </div>

                        <!-- Kartu Item Per Pasang Sepatu -->
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="bg-white rounded-2xl p-5 shadow-sm border border-neutral-100 space-y-5"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="inline-block bg-neutral-100 text-neutral-600 font-mono text-[10px] font-bold px-2 py-0.5 rounded mb-1">
                                        {{ item.item_tag_code }}
                                    </span>
                                    <h3 class="text-sm font-bold text-neutral-900">{{ item.shoe_brand_model }}</h3>
                                    <p class="text-xs text-neutral-500">
                                        {{ item.shoe_material }} • <span class="text-[#6B0B23] font-medium">{{ item.service_name }}</span>
                                    </p>
                                </div>
                                <span
                                    v-if="item.shelf_number"
                                    class="bg-neutral-50 border border-neutral-200 text-neutral-600 text-[10px] font-medium px-2 py-1 rounded-lg"
                                >
                                    Rak: {{ item.shelf_number }}
                                </span>
                            </div>

                            <!-- Stepper Progress Bar -->
                            <div class="py-2">
                                <div class="relative flex justify-between items-center">
                                    <div class="absolute top-4 left-3 right-3 h-0.5 bg-neutral-200 -z-0">
                                        <div
                                            class="h-full bg-[#6B0B23] transition-all duration-500"
                                            :style="{ width: `${(getStepIndex(item.status) / (STEPS.length - 1)) * 100}%` }"
                                        />
                                    </div>

                                    <div
                                        v-for="(step, idx) in STEPS"
                                        :key="step.status"
                                        class="flex flex-col items-center z-10"
                                    >
                                        <div
                                            :class="[
                                                'w-8 h-8 rounded-full flex items-center justify-center transition-all',
                                                idx < getStepIndex(item.status)
                                                    ? 'bg-[#6B0B23] text-white'
                                                    : idx === getStepIndex(item.status)
                                                    ? 'bg-white border-2 border-[#6B0B23] text-[#6B0B23] ring-4 ring-[#6B0B23]/10'
                                                    : 'bg-neutral-100 border border-neutral-200 text-neutral-400'
                                            ]"
                                        >
                                            <component :is="step.icon" class="w-3.5 h-3.5" />
                                        </div>
                                        <span
                                            :class="[
                                                'text-[10px] mt-2 font-medium tracking-tight text-center',
                                                idx === getStepIndex(item.status)
                                                    ? 'text-[#6B0B23] font-bold'
                                                    : idx < getStepIndex(item.status)
                                                    ? 'text-neutral-800'
                                                    : 'text-neutral-400'
                                            ]"
                                        >
                                            {{ step.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Estimasi Waktu Selesai -->
                            <div class="bg-[#6B0B23]/5 rounded-xl p-3 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <Clock class="w-4 h-4 text-[#6B0B23]" />
                                    <span class="text-neutral-600">Estimasi Selesai:</span>
                                </div>
                                <span class="font-bold text-[#6B0B23]">{{ item.estimated_completion }}</span>
                            </div>

                            <!-- Dokumentasi Foto Before & After -->
                            <div v-if="item.before_photo_url || item.after_photo_url" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-neutral-800">Dokumentasi Sepatu</p>
                                    <div class="flex bg-neutral-100 p-0.5 rounded-lg text-[11px] font-semibold">
                                        <button
                                            type="button"
                                            @click="setPhotoTab(item.id, 'before')"
                                            :class="[
                                                'px-3 py-1 rounded-md transition-all',
                                                (activePhotoTabs[item.id] || 'before') === 'before'
                                                    ? 'bg-white text-neutral-900 shadow-xs'
                                                    : 'text-neutral-500 hover:text-neutral-800'
                                            ]"
                                        >
                                            Before
                                        </button>
                                        <button
                                            type="button"
                                            @click="setPhotoTab(item.id, 'after')"
                                            :class="[
                                                'px-3 py-1 rounded-md transition-all',
                                                activePhotoTabs[item.id] === 'after'
                                                    ? 'bg-white text-[#6B0B23] shadow-xs'
                                                    : 'text-neutral-500 hover:text-neutral-800'
                                            ]"
                                        >
                                            After
                                        </button>
                                    </div>
                                </div>

                                <div class="relative aspect-video rounded-xl overflow-hidden bg-neutral-100 border border-neutral-200">
                                    <img
                                        :src="(activePhotoTabs[item.id] || 'before') === 'before'
                                            ? (item.before_photo_url || item.after_photo_url)
                                            : (item.after_photo_url || item.before_photo_url)"
                                        alt="Dokumentasi Sepatu"
                                        class="w-full h-full object-cover"
                                    />
                                    <span class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-xs text-white text-[10px] font-medium px-2 py-0.5 rounded">
                                        {{ (activePhotoTabs[item.id] || 'before') === 'before' ? 'Kondisi Awal Check-In' : 'Hasil Setelah Treatment & QC' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Tombol Konfirmasi Cepat CS -->
                    <div class="bg-neutral-900 text-white rounded-2xl p-4 flex items-center justify-between shadow-sm">
                        <div class="space-y-0.5">
                            <p class="text-xs font-bold">Ada kendala atau instruksi khusus?</p>
                            <p class="text-[11px] text-neutral-400">Hubungi teknisi bengkel Spatoe langsung</p>
                        </div>
                        <a
                            :href="`https://wa.me/6281234567890?text=Halo%20Spatoe,%20saya%20ingin%20tanya%20pesanan%20${orders[0]?.order_code}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="bg-[#6B0B23] hover:bg-[#830e2b] p-2.5 rounded-xl transition-colors"
                        >
                            <ChevronRight class="w-4 h-4 text-white" />
                        </a>
                    </div>
                </div>

                <!-- Kondisi: Pencarian Dilakukan Tapi Tidak Ditemukan -->
                <div
                    v-else-if="filters.search"
                    class="text-center py-16 bg-white rounded-2xl border border-neutral-100 p-6 space-y-2"
                >
                    <AlertCircle class="w-10 h-10 text-amber-500 mx-auto" />
                    <h3 class="text-sm font-bold text-neutral-800">Pesanan Tidak Ditemukan</h3>
                    <p class="text-xs text-neutral-500 max-w-xs mx-auto">
                        Nomor WhatsApp atau kode nota "{{ filters.search }}" belum terdaftar di sistem kami.
                    </p>
                </div>

                <!-- Kondisi Default: Belum Melakukan Pencarian -->
                <div
                    v-else
                    class="text-center py-16 bg-white rounded-2xl border border-neutral-100 p-6 space-y-3"
                >
                    <div class="w-12 h-12 rounded-full bg-[#6B0B23]/10 text-[#6B0B23] flex items-center justify-center mx-auto">
                        <ShoppingBag class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-neutral-800">Pantau Perawatan Sepatumu</h3>
                    <p class="text-xs text-neutral-500 max-w-xs mx-auto leading-relaxed">
                        Masukkan nomor WhatsApp atau kode nota yang tertera pada pesan konfirmasi untuk melihat progres cuci secara realtime.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <Footer />
    </div>
</template>