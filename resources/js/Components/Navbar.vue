<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Search,
    Wrench,
    LogOut,
    ShieldCheck,
    UserCircle2
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
            <!-- Brand Logo -->
            <div class="flex items-center gap-6">
                <Link :href="route('tracking.index')" class="flex items-center gap-2.5 transition-opacity hover:opacity-90">
                    <span class="text-2xl font-black italic tracking-tight text-[#6B0B23]">spatoe</span>
                    <span class="rounded-full bg-[#6B0B23]/10 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-[#6B0B23] border border-[#6B0B23]/20">
                        Workshop
                    </span>
                </Link>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-1.5 text-xs font-semibold">
                    <Link
                        :href="route('tracking.index')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors"
                        :class="route().current('tracking.*')
                            ? 'bg-[#6B0B23]/10 text-[#6B0B23] font-bold'
                            : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'"
                    >
                        <Search class="w-3.5 h-3.5" />
                        <span>Pelacakan Publik</span>
                    </Link>

                    <Link
                        v-if="user"
                        :href="route('workshop.index')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors"
                        :class="route().current('workshop.*')
                            ? 'bg-[#6B0B23]/10 text-[#6B0B23] font-bold'
                            : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'"
                    >
                        <Wrench class="w-3.5 h-3.5" />
                        <span>Workshop Floor</span>
                    </Link>
                </nav>
            </div>

            <!-- Right Area: Auth State -->
            <div class="flex items-center gap-3">
                <!-- If Authenticated -->
                <div v-if="user" class="flex items-center gap-3">
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

                    <!-- Workshop quick link for mobile -->
                    <Link
                        :href="route('workshop.index')"
                        class="md:hidden p-2 rounded-lg text-neutral-600 hover:bg-neutral-100"
                        title="Workshop"
                    >
                        <Wrench class="w-4 h-4 text-[#6B0B23]" />
                    </Link>

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

                <!-- If Guest -->
                <div v-else class="flex items-center gap-2">
                    <Link
                        :href="route('login')"
                        class="flex items-center gap-1.5 rounded-xl bg-[#6B0B23] px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-[#58091c] active:bg-[#450716]"
                    >
                        <ShieldCheck class="w-3.5 h-3.5" />
                        <span>Staf Login</span>
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
