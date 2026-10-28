<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Mail,
    Lock,
    Eye,
    EyeOff,
    ArrowLeft,
    AlertCircle,
    KeyRound
} from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillDemoAccount = (email) => {
    form.email = email;
    form.password = 'password123';
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased">
        <Head title="Staf Login - Spatoe Workshop" />

        <!-- Back to Public Tracking Link -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md mb-4">
            <Link
                :href="route('tracking.index')"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-[#6B0B23] transition-colors"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Pelacakan Publik</span>
            </Link>
        </div>

        <!-- Main Card -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-neutral-200/80">
                <!-- Brand Header -->
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center gap-2">
                        <span class="text-3xl font-black italic tracking-tight text-[#6B0B23]">spatoe</span>
                        <span class="rounded-full bg-[#6B0B23]/10 px-2.5 py-0.5 text-[11px] font-extrabold text-[#6B0B23] border border-[#6B0B23]/20">
                            Staff Only
                        </span>
                    </div>
                    <h1 class="text-lg font-bold text-neutral-900">Masuk ke Portal Internal</h1>
                    <p class="text-xs text-neutral-500 leading-relaxed">
                        Akses khusus teknisi workshop, admin operasional, dan armada kurir Spatoe.
                    </p>
                </div>

                <!-- Session Status Flash (if any) -->
                <div v-if="status" class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs font-medium text-emerald-800">
                    {{ status }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-neutral-700 mb-1.5">
                            Alamat Email Staf
                        </label>
                        <div class="relative">
                            <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@spatoe.com"
                                class="w-full bg-neutral-50 border border-neutral-300 rounded-xl pl-10 pr-4 py-2.5 text-xs text-neutral-800 placeholder-neutral-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                :class="{ 'border-rose-400 bg-rose-50/30': form.errors.email }"
                            />
                        </div>
                        <div v-if="form.errors.email" class="mt-1.5 flex items-center gap-1 text-xs text-rose-600 font-medium">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            <span>{{ form.errors.email }}</span>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-neutral-700 mb-1.5">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full bg-neutral-50 border border-neutral-300 rounded-xl pl-10 pr-10 py-2.5 text-xs text-neutral-800 placeholder-neutral-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#6B0B23]/20 focus:border-[#6B0B23] transition-all"
                                :class="{ 'border-rose-400 bg-rose-50/30': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 focus:outline-none"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="mt-1.5 flex items-center gap-1 text-xs text-rose-600 font-medium">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            <span>{{ form.errors.password }}</span>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded border-neutral-300 text-[#6B0B23] focus:ring-[#6B0B23]"
                            />
                            <span class="text-xs text-neutral-600">Ingat sesi saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 bg-[#6B0B23] text-white py-3 px-4 rounded-xl text-xs font-bold hover:bg-[#58091c] active:bg-[#450716] disabled:opacity-50 transition-colors shadow-xs"
                        >
                            <ShieldCheck v-if="!form.processing" class="w-4 h-4" />
                            <span v-if="form.processing">Memverifikasi Akun...</span>
                            <span v-else>Masuk ke Portal Staf</span>
                        </button>
                    </div>
                </form>

                <!-- Demo Login Shortcut Helper -->
                <div class="mt-6 pt-5 border-t border-neutral-100">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 text-center mb-2.5 flex items-center justify-center gap-1">
                        <KeyRound class="w-3.5 h-3.5" />
                        <span>Akun Uji Coba Cepat (Demo):</span>
                    </p>
                    <div class="grid grid-cols-3 gap-1.5 text-center">
                        <button
                            type="button"
                            @click="fillDemoAccount('teknisi@spatoe.com')"
                            class="rounded-lg bg-blue-50 border border-blue-200 px-2 py-1.5 text-[10px] font-bold text-blue-700 hover:bg-blue-100 transition-colors"
                        >
                            Teknisi
                        </button>
                        <button
                            type="button"
                            @click="fillDemoAccount('admin@spatoe.com')"
                            class="rounded-lg bg-purple-50 border border-purple-200 px-2 py-1.5 text-[10px] font-bold text-purple-700 hover:bg-purple-100 transition-colors"
                        >
                            Admin
                        </button>
                        <button
                            type="button"
                            @click="fillDemoAccount('kurir@spatoe.com')"
                            class="rounded-lg bg-amber-50 border border-amber-200 px-2 py-1.5 text-[10px] font-bold text-amber-700 hover:bg-amber-100 transition-colors"
                        >
                            Kurir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
