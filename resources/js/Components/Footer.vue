<script setup>
import { MessageCircle, Instagram, MapPin } from 'lucide-vue-next';
</script>

<template>
    <footer class="border-t border-neutral-800 bg-neutral-900 text-neutral-400 py-4 sm:py-5">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:gap-6 sm:px-6 lg:px-8">
            <!-- Left: Brand & Copyright -->
            <div class="flex items-center gap-2.5 shrink-0">
                <span class="text-xl font-black italic tracking-tight text-white">spatoe</span>
                <span class="text-neutral-700">|</span>
                <p class="text-[11px] text-neutral-400">
                    &copy; 2026 Spatoe. All rights reserved.
                </p>
            </div>

            <!-- Center: Compact Address Detail -->
            <div class="flex items-center gap-1.5 text-[11px] text-neutral-400 text-center sm:text-left">
                <MapPin class="w-3.5 h-3.5 text-[#6B0B23] shrink-0" />
                <span>Workshop: Jl. Gebang Wetan No. 18, Sukolilo, Surabaya (Area Kampus ITS & UNAIR)</span>
            </div>

            <!-- Right: Circular Social & CS Icon Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- WhatsApp CS Circle Button -->
                <a
                    href="https://wa.me/6281234567890?text=Halo%20Spatoe,%20saya%20ingin%20tanya%20layanan%20cuci%20sepatu"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-800 text-neutral-300 transition-all hover:bg-emerald-600 hover:text-white shadow-xs"
                    title="Chat WhatsApp CS"
                    aria-label="WhatsApp CS"
                >
                    <MessageCircle class="h-3.5 w-3.5" />
                </a>

                <!-- Instagram Circle Button -->
                <a
                    href="https://instagram.com/spatoe"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-800 text-neutral-300 transition-all hover:bg-pink-600 hover:text-white shadow-xs"
                    title="Instagram @spatoe"
                    aria-label="Instagram Spatoe"
                >
                    <Instagram class="h-3.5 w-3.5" />
                </a>
            </div>
        </div>
    </footer>
</template>
