<script setup>
import { Package, Sparkles, Clock } from 'lucide-vue-next';

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total_active: 0,
            needs_washing: 0,
            urgent_count: 0,
        }),
    },
});
</script>

<template>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="h-3 w-3 rounded-full bg-[#6B0B23]" />
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-neutral-900">
                    Workshop Floor
                </h1>
                <span class="text-xs font-semibold text-neutral-400 hidden sm:inline">/ Antrean Cuci</span>
            </div>
            <p class="mt-0.5 text-xs text-neutral-500">
                Pantau SLA pengerjaan, manajemen stasiun spesialisasi teknisi, dan perbarui tahapan 1-klik.
            </p>
        </div>

        <!-- 3 Kartu Metrik Sejajar Mentok di Sudut Kanan Atas -->
        <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
            <!-- Metrik: Total Workshop -->
            <div class="flex items-center gap-2.5 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 shadow-2xs">
                <div class="rounded-lg bg-neutral-200/80 p-1.5 text-neutral-700">
                    <Package class="h-4 w-4" />
                </div>
                <div class="leading-tight">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-neutral-400">Total Workshop</p>
                    <p class="text-xs font-extrabold text-neutral-900">
                        {{ metrics.total_active || 0 }} <span class="text-[10px] font-normal text-neutral-500">pasang</span>
                    </p>
                </div>
            </div>

            <!-- Metrik: Antrean Cuci -->
            <div class="flex items-center gap-2.5 rounded-xl border border-amber-200 bg-amber-50/50 px-3 py-1.5 shadow-2xs">
                <div class="rounded-lg bg-amber-100 p-1.5 text-amber-700">
                    <Sparkles class="h-4 w-4" />
                </div>
                <div class="leading-tight">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-amber-600">Antrean Cuci</p>
                    <p class="text-xs font-extrabold text-amber-900">
                        {{ metrics.needs_washing || 0 }} <span class="text-[10px] font-normal text-amber-700">pasang</span>
                    </p>
                </div>
            </div>

            <!-- Metrik: Mendesak -->
            <div class="flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50/50 px-3 py-1.5 shadow-2xs">
                <div class="rounded-lg bg-rose-100 p-1.5 text-rose-700">
                    <Clock class="h-4 w-4" />
                </div>
                <div class="leading-tight">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-rose-600">Mendesak</p>
                    <p class="text-xs font-extrabold text-rose-900">
                        {{ metrics.urgent_count || 0 }} <span class="text-[10px] font-normal text-rose-700">pasang</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

