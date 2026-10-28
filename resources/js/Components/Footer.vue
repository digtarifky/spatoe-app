<script setup>
import { MessageCircle, MapPin, Sparkles, Clock, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
    <footer class="border-t border-neutral-800 bg-neutral-900 text-neutral-300">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <!-- Brand & Description -->
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black italic tracking-tight text-white">spatoe</span>
                        <span class="rounded-full bg-[#6B0B23] px-2.5 py-0.5 text-[10px] font-bold text-white">
                            Care & Workshop
                        </span>
                    </div>
                    <p class="text-xs text-neutral-400 leading-relaxed max-w-md">
                        Layanan profesional perawatan, pembersihan mendalam, dan restorasi sepatu premium dengan standar mutu tinggi. Solusi cuci sepatu terpercaya khusus mahasiswa dan civitas akademika di Surabaya.
                    </p>
                    <div class="flex flex-wrap gap-2 pt-1 text-[11px] font-semibold text-neutral-400">
                        <span class="rounded-lg bg-neutral-800/80 px-2.5 py-1 flex items-center gap-1.5 border border-neutral-800">
                            <Sparkles class="w-3 h-3 text-amber-400" /> Fast & Deep Clean
                        </span>
                        <span class="rounded-lg bg-neutral-800/80 px-2.5 py-1 flex items-center gap-1.5 border border-neutral-800">
                            <Clock class="w-3 h-3 text-sky-400" /> SLA 24 - 48 Jam
                        </span>
                        <span class="rounded-lg bg-neutral-800/80 px-2.5 py-1 flex items-center gap-1.5 border border-neutral-800">
                            <ShieldCheck class="w-3 h-3 text-emerald-400" /> 6 Parameter QC
                        </span>
                    </div>
                </div>

                <!-- Campus Pickup Clusters -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">Klaster Antar-Jemput</h3>
                    <ul class="space-y-2 text-xs text-neutral-400">
                        <li class="flex items-start gap-2">
                            <MapPin class="w-4 h-4 text-[#6B0B23] shrink-0 mt-0.5" />
                            <span><strong>Klaster ITS:</strong> Keputih, Gebang, Sukolilo & sekitarnya</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <MapPin class="w-4 h-4 text-[#6B0B23] shrink-0 mt-0.5" />
                            <span><strong>Klaster UNAIR:</strong> Dharmawangsa (B) & Mulyorejo (C)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <MapPin class="w-4 h-4 text-[#6B0B23] shrink-0 mt-0.5" />
                            <span><strong>Klaster UPN:</strong> Rungkut Madya & Gunung Anyar, Surabaya</span>
                        </li>
                    </ul>
                </div>

                <!-- CS & WhatsApp Help -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">Layanan Pelanggan</h3>
                    <p class="text-xs text-neutral-400">
                        Butuh bantuan penjemputan atau konsultasi noda membandel?
                    </p>
                    <a
                        href="https://wa.me/6281234567890?text=Halo%20Spatoe,%20saya%20ingin%20tanya%20layanan%20cuci%20sepatu"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500"
                    >
                        <MessageCircle class="w-4 h-4" />
                        <span>Chat WhatsApp CS</span>
                    </a>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-neutral-800/80 pt-8 text-xs text-neutral-500 sm:flex-row">
                <p>&copy; 2026 Spatoe Shoe Care & Tracking System. All rights reserved.</p>
                <div class="flex gap-4">
                    <span>Surabaya, Jawa Timur</span>
                    <span>•</span>
                    <span>Spatoe Pass Subscription</span>
                </div>
            </div>
        </div>
    </footer>
</template>
