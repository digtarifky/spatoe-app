<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import {
    Plus,
    Trash2,
    Camera,
    Printer,
    User,
    Phone,
    MapPin,
    Tag,
    ArrowLeft,
    CheckCircle2,
    AlertCircle,
    Building2,
    Layers,
    Sparkles,
    CreditCard,
    Check,
    X,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    suggestedTag: {
        type: String,
        default: 'TAG-001',
    },
});

const CAMPUS_CLUSTERS = ['UPN', 'ITS', 'UNAIR', 'UMUM'];
const MATERIAL_OPTIONS = ['Canvas', 'Leather', 'Suede', 'Mesh', 'Kombinasi'];

const PAYMENT_METHODS = [
    { value: 'QRIS', label: 'QRIS Instant' },
    { value: 'COD', label: 'Bayar di Tempat (COD)' },
    { value: 'BANK_TRANSFER', label: 'Transfer Bank' },
    { value: 'SPATOE_PASS', label: 'Spatoe Pass Quota' },
];

const createEmptyItem = () => ({
    shoe_brand_model: '',
    shoe_material: 'Canvas',
    shoe_color: '',
    service_id: props.services[0]?.id || '',
    storage_shelf_number: '',
    defect_notes: '',
    before_photo: null,
});

const form = useForm({
    phone_number: '',
    full_name: '',
    default_address: '',
    campus_cluster: 'ITS',
    payment_status: 'PAID',
    payment_method: 'QRIS',
    items: [createEmptyItem()],
});

// Photo Previews
const photoPreviews = ref({});

const handlePhotoChange = (index, event) => {
    const file = event.target.files?.[0];
    if (file) {
        form.items[index].before_photo = file;
        if (photoPreviews.value[index]) {
            URL.revokeObjectURL(photoPreviews.value[index]);
        }
        photoPreviews.value[index] = URL.createObjectURL(file);
    }
};

const removePhoto = (index) => {
    form.items[index].before_photo = null;
    if (photoPreviews.value[index]) {
        URL.revokeObjectURL(photoPreviews.value[index]);
        delete photoPreviews.value[index];
    }
};

// Item Management
const addItem = () => {
    form.items.push(createEmptyItem());
};

const removeItem = (index) => {
    if (form.items.length <= 1) return;
    removePhoto(index);
    form.items.splice(index, 1);
};

// Debounced Customer Lookup
let debounceTimeout = null;
const isLookingUpCustomer = ref(false);
const customerFoundNotice = ref(false);

const onPhoneInput = () => {
    customerFoundNotice.value = false;
    clearTimeout(debounceTimeout);

    const cleaned = form.phone_number.replace(/[^0-9]/g, '');
    if (cleaned.length < 9) {
        return;
    }

    debounceTimeout = setTimeout(async () => {
        isLookingUpCustomer.value = true;
        try {
            const url = route('workshop.checkin.find-customer', { phone: cleaned });
            const response = await fetch(url);
            if (response.ok) {
                const data = await response.json();
                if (data.customer) {
                    form.full_name = data.customer.full_name || form.full_name;
                    form.default_address = data.customer.default_address || form.default_address;
                    if (data.customer.campus_cluster) {
                        form.campus_cluster = data.customer.campus_cluster;
                    }
                    customerFoundNotice.value = true;
                }
            }
        } catch (error) {
            console.error('Gagal memeriksa data pelanggan:', error);
        } finally {
            isLookingUpCustomer.value = false;
        }
    }, 450);
};

// Tag Sequence Preview Calculation
const getPredictedTag = (index) => {
    const baseMatch = props.suggestedTag.match(/TAG-(\d+)/i);
    const baseNumber = baseMatch ? parseInt(baseMatch[1], 10) : 1;
    const itemNumber = baseNumber + index;
    return `TAG-${String(itemNumber).padStart(3, '0')}`;
};

// Total Amount Computed
const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        const found = props.services.find((s) => s.id === Number(item.service_id));
        return sum + (found ? Number(found.base_price) : 0);
    }, 0);
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const submitCheckIn = () => {
    form.post(route('workshop.checkin.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

onUnmounted(() => {
    Object.values(photoPreviews.value).forEach((url) => {
        if (url) URL.revokeObjectURL(url);
    });
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-neutral-100/70 text-neutral-900 font-sans antialiased">
        <Head title="Intake Meja Kasir & Tagging - Spatoe" />

        <!-- Navigation Bar -->
        <Navbar />

        <!-- Main Content Area -->
        <main class="flex-1 py-8 sm:py-10">
            <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                <!-- Header Card & Quick Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white rounded-2xl p-6 sm:p-7 shadow-xs border border-neutral-200">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('workshop.index')"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 hover:text-neutral-800 transition-colors mr-1"
                            >
                                <ArrowLeft class="w-4 h-4" />
                                <span>Kembali ke Workshop</span>
                            </Link>
                            <span class="text-neutral-300">/</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#6B0B23]">Meja Kasir</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                            Intake Meja Kasir & Tagging
                        </h1>
                        <p class="text-xs sm:text-sm text-neutral-500">
                            Registrasi sepatu pelanggan baru, pasang nomor tag fisik waterproof, dan terbitkan nota pesanan.
                        </p>
                    </div>

                    <!-- Suggested Tag Badge -->
                    <div class="flex items-center sm:flex-col sm:items-end justify-between sm:justify-center bg-[#6B0B23]/5 border border-[#6B0B23]/15 rounded-2xl p-4 sm:px-5">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#6B0B23]">
                            <Tag class="w-3.5 h-3.5" />
                            <span>Tag Berikutnya</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-black font-mono text-[#6B0B23] mt-0.5">
                            {{ suggestedTag }}
                        </p>
                    </div>
                </div>

                <!-- Form Intake Container -->
                <form @submit.prevent="submitCheckIn" class="space-y-6 sm:space-y-8">
                    <!-- SEKSI 1: DATA PELANGGAN -->
                    <section class="bg-white rounded-2xl p-6 sm:p-7 shadow-xs border border-neutral-200 space-y-5">
                        <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-[#6B0B23]/10 text-[#6B0B23] flex items-center justify-center font-bold">
                                    <User class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-neutral-900">1. Data Pelanggan</h2>
                                    <p class="text-xs text-neutral-500">Masukkan kontak WhatsApp untuk integrasi link lacak realtime</p>
                                </div>
                            </div>
                            <span
                                v-if="customerFoundNotice"
                                class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-3 py-1 rounded-full animate-fade-in"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                <span>Pelanggan Lama Ditemukan</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- Nomor WhatsApp -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                    Nomor WhatsApp <span class="text-rose-600">*</span>
                                </label>
                                <div class="relative">
                                    <Phone class="w-4 h-4 text-neutral-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        v-model="form.phone_number"
                                        @input="onPhoneInput"
                                        type="tel"
                                        placeholder="081234567890"
                                        class="w-full bg-neutral-50 border border-neutral-200 rounded-xl pl-10 pr-9 py-2.5 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                        :class="{ 'border-rose-300 ring-2 ring-rose-100': form.errors.phone_number }"
                                        required
                                    />
                                    <Loader2
                                        v-if="isLookingUpCustomer"
                                        class="w-4 h-4 text-[#6B0B23] animate-spin absolute right-3 top-1/2 -translate-y-1/2"
                                    />
                                </div>
                                <p v-if="form.errors.phone_number" class="text-xs text-rose-600 mt-1 font-medium">
                                    {{ form.errors.phone_number }}
                                </p>
                            </div>

                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                    Nama Lengkap <span class="text-rose-600">*</span>
                                </label>
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    placeholder="Nama lengkap pelanggan"
                                    class="w-full bg-neutral-50 border border-neutral-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                    :class="{ 'border-rose-300 ring-2 ring-rose-100': form.errors.full_name }"
                                    required
                                />
                                <p v-if="form.errors.full_name" class="text-xs text-rose-600 mt-1 font-medium">
                                    {{ form.errors.full_name }}
                                </p>
                            </div>

                            <!-- Klaster Kampus / Area -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                    Klaster Kampus / Area
                                </label>
                                <div class="relative">
                                    <Building2 class="w-4 h-4 text-neutral-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <select
                                        v-model="form.campus_cluster"
                                        class="w-full bg-neutral-50 border border-neutral-200 rounded-xl pl-10 pr-8 py-2.5 text-xs sm:text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                    >
                                        <option v-for="cluster in CAMPUS_CLUSTERS" :key="cluster" :value="cluster">
                                            Klaster {{ cluster }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Alamat Kos / Pengantaran -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                    Alamat Kos / Pengiriman <span class="text-rose-600">*</span>
                                </label>
                                <div class="relative">
                                    <MapPin class="w-4 h-4 text-neutral-400 absolute left-3.5 top-3 pointer-events-none" />
                                    <textarea
                                        v-model="form.default_address"
                                        rows="2"
                                        placeholder="Nama kos, nomor kamar, jalan, dan kelurahan"
                                        class="w-full bg-neutral-50 border border-neutral-200 rounded-xl pl-10 pr-3.5 py-2.5 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all resize-none"
                                        :class="{ 'border-rose-300 ring-2 ring-rose-100': form.errors.default_address }"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.default_address" class="text-xs text-rose-600 mt-1 font-medium">
                                    {{ form.errors.default_address }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- SEKSI 2: RINCIAN FISIK SEPATU (MULTI-ITEM DINAMIS) -->
                    <section class="bg-white rounded-2xl p-6 sm:p-7 shadow-xs border border-neutral-200 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-neutral-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-[#6B0B23]/10 text-[#6B0B23] flex items-center justify-center font-bold">
                                    <Tag class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-neutral-900">2. Rincian Fisik Sepatu</h2>
                                    <p class="text-xs text-neutral-500">Tambahkan seluruh sepatu yang masuk ke nota pengerjaan ini</p>
                                </div>
                            </div>

                            <!-- Tombol Tambah Sepatu -->
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center justify-center gap-1.5 bg-[#6B0B23]/10 hover:bg-[#6B0B23]/20 text-[#6B0B23] px-3.5 py-2 rounded-xl text-xs font-bold transition-colors"
                            >
                                <Plus class="w-4 h-4" />
                                <span>Tambah Sepatu</span>
                            </button>
                        </div>

                        <!-- Baris Sepatu Per Item -->
                        <div class="space-y-6">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 sm:p-6 relative transition-all"
                            >
                                <!-- Header Item -->
                                <div class="flex items-center justify-between border-b border-neutral-200/60 pb-3 mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#6B0B23] text-white text-xs font-black">
                                            {{ index + 1 }}
                                        </span>
                                        <h3 class="text-sm font-bold text-neutral-800">
                                            Pasang Sepatu ke-{{ index + 1 }}
                                        </h3>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-white text-[#6B0B23] border border-neutral-200 shadow-2xs">
                                            <Tag class="w-3 h-3 text-[#6B0B23]" />
                                            {{ getPredictedTag(index) }}
                                        </span>
                                    </div>

                                    <!-- Tombol Hapus Sepatu -->
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition-colors font-semibold"
                                        title="Hapus baris sepatu ini"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        <span class="hidden sm:inline">Hapus</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <!-- Merk & Seri Sepatu -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Merk & Seri Sepatu <span class="text-rose-600">*</span>
                                        </label>
                                        <input
                                            v-model="item.shoe_brand_model"
                                            type="text"
                                            placeholder="contoh: Nike Air Jordan 1 Low / New Balance 550"
                                            class="w-full bg-white border border-neutral-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                            :class="{ 'border-rose-300 ring-2 ring-rose-100': form.errors[`items.${index}.shoe_brand_model`] }"
                                            required
                                        />
                                        <p v-if="form.errors[`items.${index}.shoe_brand_model`]" class="text-xs text-rose-600 mt-1 font-medium">
                                            {{ form.errors[`items.${index}.shoe_brand_model`] }}
                                        </p>
                                    </div>

                                    <!-- Pilihan Paket Layanan -->
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Paket Layanan <span class="text-rose-600">*</span>
                                        </label>
                                        <select
                                            v-model="item.service_id"
                                            class="w-full bg-white border border-neutral-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                            required
                                        >
                                            <option v-for="srv in services" :key="srv.id" :value="srv.id">
                                                {{ srv.service_name }} ({{ formatRupiah(srv.base_price) }}) - {{ srv.estimated_hours }} Jam
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Jenis Material -->
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Material Sepatu <span class="text-rose-600">*</span>
                                        </label>
                                        <select
                                            v-model="item.shoe_material"
                                            class="w-full bg-white border border-neutral-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                            required
                                        >
                                            <option v-for="mat in MATERIAL_OPTIONS" :key="mat" :value="mat">
                                                {{ mat }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Warna Dominan -->
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Warna Sepatu
                                        </label>
                                        <input
                                            v-model="item.shoe_color"
                                            type="text"
                                            placeholder="e.g. White / Black / Red Gum"
                                            class="w-full bg-white border border-neutral-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                        />
                                    </div>

                                    <!-- Nomor Rak Penyimpanan -->
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Nomor Rak Penyimpanan
                                        </label>
                                        <div class="relative">
                                            <Layers class="w-4 h-4 text-neutral-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                            <input
                                                v-model="item.storage_shelf_number"
                                                type="text"
                                                placeholder="e.g. A-01 / B-04"
                                                class="w-full bg-white border border-neutral-200 rounded-xl pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-mono text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                            />
                                        </div>
                                    </div>

                                    <!-- Catatan Cacat Bawaan (Defect Notes) -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Catatan Cacat Fisik Awal (Defect Notes)
                                        </label>
                                        <textarea
                                            v-model="item.defect_notes"
                                            rows="2"
                                            placeholder="Catatan goresan, noda membandel, sobekan, atau sol unglued saat intake..."
                                            class="w-full bg-white border border-neutral-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all resize-none"
                                        />
                                    </div>

                                    <!-- Unggah Foto Kondisi Awal (Camera / File) -->
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">
                                            Foto Kondisi Awal (Before)
                                        </label>

                                        <!-- Preview Card if photo chosen -->
                                        <div
                                            v-if="photoPreviews[index]"
                                            class="relative aspect-4/3 aspect-[4/3] rounded-xl overflow-hidden bg-neutral-200 border border-neutral-300 group"
                                        >
                                            <img
                                                :src="photoPreviews[index]"
                                                alt="Preview Before"
                                                class="w-full h-full object-cover"
                                            />
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="removePhoto(index)"
                                                    class="bg-rose-600 text-white p-2 rounded-lg hover:bg-rose-700 transition-colors shadow-xs"
                                                    title="Hapus foto"
                                                >
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <span class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded font-mono">
                                                Foto Siap
                                            </span>
                                        </div>

                                        <!-- Upload / Capture Button -->
                                        <label
                                            v-else
                                            class="flex flex-col items-center justify-center border-2 border-dashed border-neutral-300 hover:border-[#6B0B23] rounded-xl p-4 text-center cursor-pointer bg-white transition-colors aspect-4/3 aspect-[4/3]"
                                        >
                                            <Camera class="w-6 h-6 text-neutral-400 mb-1" />
                                            <span class="text-xs font-bold text-neutral-700">Buka Kamera / Foto</span>
                                            <span class="text-[10px] text-neutral-400 mt-0.5">JPG / PNG maks 5MB</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                capture="environment"
                                                class="hidden"
                                                @change="handlePhotoChange(index, $event)"
                                            />
                                        </label>
                                        <p v-if="form.errors[`items.${index}.before_photo`]" class="text-xs text-rose-600 mt-1 font-medium">
                                            {{ form.errors[`items.${index}.before_photo`] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- SEKSI 3: RINGKASAN TAGIHAN & SUBMIT -->
                    <section class="bg-white rounded-2xl p-6 sm:p-7 shadow-xs border border-neutral-200 space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-[#6B0B23]/10 text-[#6B0B23] flex items-center justify-center font-bold">
                                    <CheckCircle2 class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-neutral-900">3. Pembayaran & Konfirmasi</h2>
                                    <p class="text-xs text-neutral-500">Pilih metode bayar dan terbitkan nota transaksi</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block">Total Tagihan</span>
                                <p class="text-xl sm:text-2xl font-black text-[#6B0B23]">
                                    {{ formatRupiah(totalAmount) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                            <!-- Metode Pembayaran -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                                    Metode Pembayaran <span class="text-rose-600">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="method in PAYMENT_METHODS"
                                        :key="method.value"
                                        type="button"
                                        @click="form.payment_method = method.value"
                                        :class="[
                                            'p-3 rounded-xl border text-left text-xs font-bold transition-all flex flex-col justify-between h-18',
                                            form.payment_method === method.value
                                                ? 'border-[#6B0B23] bg-[#6B0B23]/5 text-[#6B0B23] ring-2 ring-[#6B0B23]/15'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-700 hover:bg-neutral-100'
                                        ]"
                                    >
                                        <div class="flex items-center justify-between">
                                            <CreditCard class="w-4 h-4" />
                                            <Check v-if="form.payment_method === method.value" class="w-3.5 h-3.5 text-[#6B0B23]" />
                                        </div>
                                        <span>{{ method.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Status Pelunasan -->
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                                    Status Pelunasan <span class="text-rose-600">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="form.payment_status = 'PAID'"
                                        :class="[
                                            'p-3 rounded-xl border text-center text-xs font-bold transition-all flex flex-col items-center justify-center gap-1 h-18',
                                            form.payment_status === 'PAID'
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-200'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100'
                                        ]"
                                    >
                                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                        <span>LUNAS (PAID)</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.payment_status = 'UNPAID'"
                                        :class="[
                                            'p-3 rounded-xl border text-center text-xs font-bold transition-all flex flex-col items-center justify-center gap-1 h-18',
                                            form.payment_status === 'UNPAID'
                                                ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-200'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100'
                                        ]"
                                    >
                                        <AlertCircle class="w-4 h-4 text-amber-600" />
                                        <span>BELUM LUNAS</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Item Pesanan -->
                        <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200/70 text-xs text-neutral-600 space-y-2">
                            <div class="flex items-center justify-between font-bold text-neutral-800">
                                <span>Rincian ({{ form.items.length }} Pasang Sepatu):</span>
                                <span>Subtotal</span>
                            </div>
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="flex items-center justify-between text-neutral-600"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[#6B0B23] font-bold">{{ getPredictedTag(idx) }}</span>
                                    <span>{{ item.shoe_brand_model || `Sepatu #${idx + 1}` }}</span>
                                    <span class="text-neutral-400">• {{ services.find(s => s.id === Number(item.service_id))?.service_name || '-' }}</span>
                                </div>
                                <span class="font-mono font-semibold text-neutral-800">
                                    {{ formatRupiah(services.find(s => s.id === Number(item.service_id))?.base_price || 0) }}
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-[#6B0B23] hover:bg-[#54081B] text-white py-3.5 sm:py-4 px-6 rounded-xl text-sm sm:text-base font-extrabold flex items-center justify-center gap-2 transition-all shadow-md disabled:opacity-50"
                            >
                                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                                <Printer v-else class="w-5 h-5" />
                                <span>{{ form.processing ? 'Menyimpan & Menerbitkan...' : 'Check-In & Terbitkan Tag Fisik' }}</span>
                            </button>
                        </div>
                    </section>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <Footer />
    </div>
</template>

