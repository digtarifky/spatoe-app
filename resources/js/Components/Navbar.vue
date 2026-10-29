<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LogOut } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const roleLabel = computed(() => {
    switch (user.value?.role) {
        case 'admin':
            return 'Administrator';
        case 'technician':
            return 'Teknisi Workshop';
        case 'courier':
            return 'Kurir Antar-Jemput';
        default:
            return user.value?.role || 'Staf';
    }
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-md shadow-2xs">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <!-- Brand Logo (Clean & Minimal) -->
            <div class="flex items-center">
                <Link :href="route('tracking.index')" class="flex items-center transition-opacity hover:opacity-90">
                    <span class="text-2xl font-black italic tracking-tight text-[#6B0B23]">spatoe</span>
                </Link>
            </div>

            <!-- Right Area: Only Visible When User is Authenticated -->
            <div v-if="user" class="flex items-center gap-3">
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
