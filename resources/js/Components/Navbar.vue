<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Wrench,
    LogOut
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'admin':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'technician':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'courier':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        default:
            return 'bg-neutral-100 text-neutral-700 border-neutral-200';
    }
};
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
                <!-- Workshop Navigation Link -->
                <Link
                    :href="route('workshop.index')"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                    :class="route().current('workshop.*')
                        ? 'bg-[#6B0B23]/10 text-[#6B0B23] font-bold'
                        : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'"
                >
                    <Wrench class="w-3.5 h-3.5" />
                    <span>Workshop Floor</span>
                </Link>

                <!-- User Profile & Role Info -->
                <div class="hidden sm:flex flex-col items-end">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-neutral-800">{{ user.name }}</span>
                        <span
                            v-if="user.role"
                            class="rounded-full border px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider"
                            :class="getRoleBadgeClass(user.role)"
                        >
                            {{ user.role }}
                        </span>
                    </div>
                    <span class="text-[10px] text-neutral-400 font-mono">{{ user.email }}</span>
                </div>

                <!-- Logout Button -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center gap-1.5 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200"
                    title="Keluar dari akun"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Logout</span>
                </Link>
            </div>
        </div>
    </header>
</template>
