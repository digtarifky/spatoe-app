<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-neutral-100/70 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link :href="route('tracking.index')">
                <span class="text-3xl font-black italic tracking-tight text-[#6B0B23]">spatoe</span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-2xl border border-neutral-200">
            <slot />
        </div>
    </div>
</template>
