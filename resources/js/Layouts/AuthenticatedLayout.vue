<script setup>
import Navbar from '@/Components/Navbar.vue';

defineProps({
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
</script>

<template>
    <div class="min-h-screen bg-neutral-100/70 text-neutral-900">
        <Navbar
            :search="search"
            :show-search="showSearch"
            @update:search="emit('update:search', $event)"
            @search="emit('search', $event)"
        />

        <header v-if="$slots.header" class="border-b border-neutral-200 bg-white shadow-2xs">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
