<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const searchQuery = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get(
        route('tracking.index'),
        { search: searchQuery.value },
        { preserveState: true, replace: true }
    );
};
</script>

<template>
    <Head title="Pelacakan Pesanan - Spatoe" />

    <div class="min-h-screen bg-slate-50 text-slate-800">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl font-black tracking-tight text-amber-600">SPATOE</span>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Tracking</span>
                </div>
                <nav class="flex items-center space-x-4">
                    <a :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                        Staff Login
                    </a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <!-- Search Hero Card -->
            <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 p-8 text-white shadow-lg">
                <h1 class="text-2xl font-bold sm:text-3xl">Lacak Status Cuci Sepatu</h1>
                <p class="mt-2 text-sm text-amber-100 sm:text-base">
                    Masukkan nomor WhatsApp atau kode pesanan untuk melihat progres pencucian dan hasil inspeksi QC.
                </p>

                <form @submit.prevent="handleSearch" class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Contoh: 081234567890 atau ORD-20261028-001"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3.5 text-slate-800 placeholder-slate-400 shadow-sm focus:ring-2 focus:ring-amber-300"
                        />
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Lacak Pesanan
                    </button>
                </form>
            </div>

            <!-- Search Results -->
            <div class="mt-8 space-y-6">
                <!-- If No Search Given -->
                <div v-if="!filters.search" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">Belum Ada Pencarian</h3>
                    <p class="mt-1 text-sm text-slate-500">Ketik nomor HP terdaftar atau nomor nota untuk melihat status sepatu.</p>
                </div>

                <!-- If Search Given but Empty -->
                <div v-else-if="orders.length === 0" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">Pesanan Tidak Ditemukan</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Tidak ada pesanan yang cocok dengan "{{ filters.search }}". Pastikan nomor HP atau nomor nota sudah sesuai.
                    </p>
                </div>

                <!-- Order Cards -->
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <!-- Order Header -->
                    <div class="border-b bg-slate-50/75 px-6 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Nota</span>
                                <h3 class="text-lg font-bold text-slate-900">{{ order.order_code }}</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="order.payment_status === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ order.payment_status }}
                                </span>
                                <span
                                    v-if="order.is_qc_passed"
                                    class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800"
                                >
                                    QC PASSED
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500">
                            <div>Pemilik: <span class="font-medium text-slate-700">{{ order.customer?.full_name }}</span></div>
                            <div>Tanggal Masuk: <span class="font-medium text-slate-700">{{ order.created_at }}</span></div>
                            <div>Metode: <span class="font-medium text-slate-700">{{ order.payment_method }}</span></div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="divide-y divide-slate-100 px-6 py-4">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="py-4 first:pt-0 last:pb-0"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">
                                            {{ item.item_tag_code }}
                                        </span>
                                        <h4 class="text-base font-semibold text-slate-900">{{ item.shoe_brand_model }}</h4>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Layanan: <span class="font-medium text-slate-700">{{ item.service?.service_name }}</span> |
                                        Bahan: <span class="font-medium text-slate-700">{{ item.shoe_material }}</span>
                                        <span v-if="item.shoe_color"> | Warna: {{ item.shoe_color }}</span>
                                    </p>
                                </div>
                                <span class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                    {{ item.status }}
                                </span>
                            </div>

                            <!-- QC Status Box -->
                            <div v-if="item.qc" class="mt-3 rounded-xl bg-slate-50 p-3 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-700">Inspeksi Quality Control (QC):</span>
                                    <span
                                        class="font-bold"
                                        :class="item.qc.qc_passed ? 'text-emerald-600' : 'text-amber-600'"
                                    >
                                        {{ item.qc.qc_passed ? 'Lulus QC' : 'Proses QC' }}
                                    </span>
                                </div>
                                <p v-if="item.qc.inspected_at" class="mt-0.5 text-slate-400">
                                    Waktu inspeksi: {{ item.qc.inspected_at }}
                                </p>
                            </div>

                            <!-- Photos Section (Before / After) -->
                            <div
                                v-if="(item.photos_before && item.photos_before.length > 0) || (item.photos_after && item.photos_after.length > 0)"
                                class="mt-4 grid grid-cols-2 gap-4"
                            >
                                <!-- Before Photos -->
                                <div v-if="item.photos_before && item.photos_before.length > 0">
                                    <span class="text-xs font-semibold text-slate-500">Foto Sebelum (Before):</span>
                                    <div class="mt-1 flex gap-2 overflow-x-auto">
                                        <img
                                            v-for="photo in item.photos_before"
                                            :key="photo.id"
                                            :src="photo.url"
                                            alt="Foto Before"
                                            class="h-24 w-24 rounded-lg object-cover border"
                                        />
                                    </div>
                                </div>

                                <!-- After Photos -->
                                <div v-if="item.photos_after && item.photos_after.length > 0">
                                    <span class="text-xs font-semibold text-slate-500">Foto Sesudah (After):</span>
                                    <div class="mt-1 flex gap-2 overflow-x-auto">
                                        <img
                                            v-for="photo in item.photos_after"
                                            :key="photo.id"
                                            :src="photo.url"
                                            alt="Foto After"
                                            class="h-24 w-24 rounded-lg object-cover border"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
