<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    LogOut,
    Search,
    X,
    Sparkles,
    Wind,
    ShieldCheck,
    Truck,
    LayoutGrid,
    Receipt,
} from 'lucide-vue-next';

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    showSearch: {
        type: Boolean,
        default: null,
    },
});

const emit = defineEmits(['update:search', 'search']);

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const localSearch = ref(props.search || '');

watch(
    () => props.search,
    (newVal) => {
        localSearch.value = newVal || '';
    }
);

// Tampilkan search bar jika user terautentikasi dan berada di rute workshop / kasir
const isSearchVisible = computed(() => {
    if (!user.value) return false;
    if (props.showSearch !== null) return props.showSearch;
    try {
        return Boolean(route().current('workshop.*') || route().current('cashier.*'));
    } catch {
        return false;
    }
});

const onSearchInput = (e) => {
    const value = e.target.value;
    localSearch.value = value;
    emit('update:search', value);
};

const onSearchSubmit = () => {
    emit('search', localSearch.value);
    try {
        if (route().current('workshop.*')) {
            router.get(
                route('workshop.index'),
                { search: localSearch.value },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        } else if (route().current('cashier.*')) {
            router.get(
                route('cashier.index'),
                { search: localSearch.value },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }
    } catch {
        // Fallback jika route helper tidak tersedia
    }
};

const clearSearch = () => {
    localSearch.value = '';
    emit('update:search', '');
    emit('search', '');
    try {
        if (route().current('workshop.*')) {
            router.get(
                route('workshop.index'),
                { search: '' },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        } else if (route().current('cashier.*')) {
            router.get(
                route('cashier.index'),
                { search: '' },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }
    } catch {
        // Fallback
    }
};

const roleLabel = computed(() => {
    switch (user.value?.role) {
        case 'admin':
            return 'Administrator';
        case 'cashier':
            return 'Kasir / Front Desk';
        case 'technician':
            return 'Teknisi Workshop';
        case 'courier':
            return 'Kurir Antar-Jemput';
        default:
            return user.value?.role || 'Staf';
    }
});

// Penanda badge "Tugas Anda" di Navbar
const dutyBadge = computed(() => {
    if (!user.value) return null;

    if (user.value.role === 'admin' || user.value.role === 'supervisor') {
        return {
            label: 'Semua Pos (Admin)',
            icon: LayoutGrid,
            badgeClass: 'bg-[#6B0B23]/10 text-[#6B0B23] border-[#6B0B23]/20',
        };
    }

    if (user.value.role === 'cashier') {
        return {
            label: 'Front Desk & Kasir',
            icon: Receipt,
            badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        };
    }

    if (user.value.role === 'technician') {
        switch (user.value.assigned_station) {
            case 'WASHING':
                return {
                    label: 'Pos Cuci & Sikat',
                    icon: Sparkles,
                    badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
                };
            case 'DRYING':
                return {
                    label: 'Pos Pengeringan',
                    icon: Wind,
                    badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                };
            case 'QC_INSPECTION':
                return {
                    label: 'Pos QC',
                    icon: ShieldCheck,
                    badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                };
            case 'READY_DELIVERY':
                return {
                    label: 'Pos Siap Antar',
                    icon: Truck,
                    badgeClass: 'bg-rose-50 text-rose-700 border-rose-200',
                };
            case 'ALL':
            default:
                return {
                    label: 'Semua Pos (Rotasi Bebas)',
                    icon: LayoutGrid,
                    badgeClass: 'bg-neutral-100 text-neutral-700 border-neutral-200',
                };
        }
    }

    return null;
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-md shadow-2xs">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 sm:gap-4 px-4 py-2.5 sm:px-6 lg:px-8">
            <!-- Left Area: Logo & Search Bar -->
            <div class="flex items-center gap-3 sm:gap-6 flex-1 min-w-0">
                <!-- Brand Logo (Clean & Minimal) -->
                <Link :href="route('tracking.index')" class="flex items-center flex-shrink-0 transition-opacity hover:opacity-90">
                    <span class="text-2xl font-black italic tracking-tight text-[#6B0B23]">spatoe</span>
                </Link>

                <!-- Bilah Pencarian di Sebelah Kanan Logo -->
                <form
                    v-if="isSearchVisible"
                    @submit.prevent="onSearchSubmit"
                    class="relative flex-1 max-w-xs sm:max-w-sm md:max-w-md"
                >
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-neutral-400" />
                    <input
                        :value="localSearch"
                        @input="onSearchInput"
                        type="text"
                        placeholder="Cari tag fisik atau model sepatu..."
                        class="w-full rounded-xl border border-neutral-200 bg-neutral-50/80 py-1.5 pl-10 pr-9 text-xs text-neutral-800 placeholder-neutral-400 transition-all focus:border-[#6B0B23] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6B0B23]/20"
                    />
                    <button
                        v-if="localSearch"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-neutral-400 hover:text-neutral-600 transition-colors"
                        title="Hapus pencarian"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </form>
                <!-- Navigation Switcher untuk Admin: Kasir vs Workshop -->
                <nav v-if="user && ['admin', 'supervisor'].includes(user.role)" class="hidden md:flex items-center gap-1 bg-neutral-100 p-1 rounded-xl border border-neutral-200">
                    <Link
                        :href="route('cashier.index')"
                        :class="[
                            'px-3 py-1 text-xs font-bold rounded-lg transition-all',
                            route().current('cashier.*')
                                ? 'bg-white text-[#6B0B23] shadow-2xs font-extrabold'
                                : 'text-neutral-600 hover:text-neutral-900'
                        ]"
                    >
                        Kasir
                    </Link>
                    <Link
                        :href="route('workshop.index')"
                        :class="[
                            'px-3 py-1 text-xs font-bold rounded-lg transition-all',
                            route().current('workshop.*')
                                ? 'bg-white text-[#6B0B23] shadow-2xs font-extrabold'
                                : 'text-neutral-600 hover:text-neutral-900'
                        ]"
                    >
                        Workshop
                    </Link>
                </nav>
            </div>

            <!-- Right Area: Only Visible When User is Authenticated -->
            <div v-if="user" class="flex items-center gap-3 flex-shrink-0">
                <!-- Badge Penanda "Tugas Anda" (Di sebelah kiri info nama/role pengguna) -->
                <div
                    v-if="dutyBadge"
                    class="hidden sm:flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs shadow-2xs font-semibold"
                    :class="dutyBadge.badgeClass"
                >
                    <component :is="dutyBadge.icon" class="h-3.5 w-3.5" />
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Tugas:</span>
                    <span class="text-xs font-extrabold">
                        {{ dutyBadge.label }}
                    </span>
                </div>

                <!-- User Name & Role Info -->
                <div class="flex flex-col items-end leading-tight">
                    <span class="text-xs sm:text-sm font-bold text-neutral-900">
                        {{ user.name }}
                    </span>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-[#6B0B23]">
                        {{ roleLabel }}
                    </span>
                </div>

                <!-- Tombol Logout (Hanya Icon LogOut) -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center justify-center p-2 rounded-xl text-neutral-500 hover:text-rose-600 hover:bg-rose-50 border border-neutral-200 bg-neutral-50 transition-colors"
                    title="Keluar dari akun"
                    aria-label="Logout"
                >
                    <LogOut class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </header>
</template>
