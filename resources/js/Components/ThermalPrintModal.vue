<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import JsBarcode from 'jsbarcode';
import {
    Printer,
    X,
    Receipt,
    Tag,
    Sliders,
    Copy,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    order: {
        type: Object,
        default: () => null,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
// Admin, supervisor, dan kasir diizinkan mencetak struk nota kasir; teknisi hanya tag rak
const canPrintReceipt = computed(() => ['admin', 'supervisor', 'cashier'].includes(currentUser.value?.role));

// Print Configuration State
const printMode = ref(canPrintReceipt.value ? 'receipt' : 'shelf_tag'); // 'receipt' | 'shelf_tag'
const paperWidth = ref('58mm'); // '58mm' | '80mm'
const isCopied = ref(false);

const customerName = computed(() => {
    return props.order?.customer?.full_name || props.order?.customer_name || 'Pelanggan Walk-In';
});

const customerPhone = computed(() => {
    return props.order?.customer?.phone_number || props.order?.customer_phone || '-';
});

const orderItems = computed(() => {
    return props.order?.items || [];
});

const isPaid = computed(() => {
    return props.order?.payment_status === 'PAID';
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Render Barcodes via JsBarcode
const renderBarcodes = async () => {
    if (!props.isOpen || !props.order) return;
    await nextTick();

    // Mode 1: Barcode Struk Nota (CODE128 dari order_code) - Hanya jika berhak
    if (printMode.value === 'receipt' && canPrintReceipt.value) {
        const receiptBarcodeEl = document.querySelector('.receipt-barcode-svg');
        if (receiptBarcodeEl && props.order.order_code) {
            try {
                JsBarcode(receiptBarcodeEl, props.order.order_code, {
                    format: 'CODE128',
                    width: paperWidth.value === '58mm' ? 1.3 : 1.7,
                    height: 38,
                    displayValue: true,
                    fontSize: 10,
                    font: 'monospace',
                    textMargin: 2,
                    margin: 0,
                });
            } catch (err) {
                console.error('Gagal merender barcode struk:', err);
            }
        }
    }

    // Mode 2: Barcode Tag Rak Fisik (CODE128 dari item_tag_code per pasang sepatu)
    if (printMode.value === 'shelf_tag' || !canPrintReceipt.value) {
        const tagBarcodeEls = document.querySelectorAll('.tag-barcode-svg');
        tagBarcodeEls.forEach((el) => {
            const code = el.getAttribute('data-barcode');
            if (code) {
                try {
                    JsBarcode(el, code, {
                        format: 'CODE128',
                        width: paperWidth.value === '58mm' ? 1.6 : 2.0,
                        height: 44,
                        displayValue: true,
                        fontSize: 11,
                        font: 'monospace',
                        textMargin: 2,
                        margin: 0,
                    });
                } catch (err) {
                    console.error('Gagal merender barcode tag:', err);
                }
            }
        });
    }
};

// Injeksi dynamic print stylesheet ke dokumen utama untuk presisi @page thermal printer
const injectDynamicPrintStyles = (width) => {
    let styleEl = document.getElementById('spatoe-thermal-dynamic-print-style');
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'spatoe-thermal-dynamic-print-style';
        document.head.appendChild(styleEl);
    }

    styleEl.innerHTML = `
        @media print {
            @page {
                size: ${width} auto !important;
                margin: 0 !important;
            }
            html, body {
                width: ${width} !important;
                min-width: ${width} !important;
                max-width: ${width} !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: visible !important;
            }
            body * {
                visibility: hidden !important;
            }
            .no-print,
            .no-print * {
                display: none !important;
            }
            #thermal-print-area,
            #thermal-print-area * {
                visibility: visible !important;
            }
            #thermal-print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: ${width} !important;
                min-width: ${width} !important;
                max-width: ${width} !important;
                margin: 0 !important;
                padding: 2mm 3mm !important;
                box-shadow: none !important;
                background: #ffffff !important;
                color: #000000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: visible !important;
            }
            .break-after-page {
                page-break-after: always !important;
                break-after: page !important;
            }
            .break-inside-avoid {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    `;
};

// Metode cetak terisolasi via Hidden Iframe untuk menghindari pemotongan bawah dan interferensi layout window
const printViaIsolatedIframe = (targetEl, width) => {
    let iframe = document.getElementById('thermal-print-iframe');
    if (iframe) {
        iframe.remove();
    }

    iframe = document.createElement('iframe');
    iframe.id = 'thermal-print-iframe';
    iframe.name = 'thermal-print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.style.visibility = 'hidden';
    document.body.appendChild(iframe);

    // Salin stylesheet aplikasi agar class Tailwind tetap berfungsi di dalam iframe
    const styleLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
        .filter((node) => node.id !== 'spatoe-thermal-dynamic-print-style')
        .map((node) => node.outerHTML)
        .join('\n');

    const iframeDoc = iframe.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="utf-8">
            <title>Cetak Thermal Spatoe - ${props.order?.order_code || ''}</title>
            ${styleLinks}
            <style>
                @page {
                    size: ${width} auto;
                    margin: 0;
                }
                @media print {
                    @page {
                        size: ${width} auto;
                        margin: 0;
                    }
                }
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #ffffff !important;
                    color: #000000 !important;
                    width: ${width} !important;
                    min-width: ${width} !important;
                    max-width: ${width} !important;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                    overflow: visible !important;
                }
                * {
                    box-sizing: border-box !important;
                }
                .thermal-print-wrapper {
                    width: ${width} !important;
                    max-width: ${width} !important;
                    margin: 0 auto !important;
                    padding: 2mm 3mm !important;
                    background: #ffffff !important;
                    color: #000000 !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                    overflow: visible !important;
                }
                .break-after-page {
                    page-break-after: always !important;
                    break-after: page !important;
                }
                .break-inside-avoid {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }
                svg {
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                }
            </style>
        </head>
        <body>
            <div class="thermal-print-wrapper">
                ${targetEl.innerHTML}
            </div>
        </body>
        </html>
    `);
    iframeDoc.close();

    // Beri jeda kecil agar rendering barcode SVG dan style dalam iframe tuntas
    setTimeout(() => {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (err) {
            console.warn('Iframe print tidak didukung atau terblokir, fallback ke window.print:', err);
            window.print();
        }
    }, 250);
};

const handlePrint = async () => {
    await renderBarcodes();
    await nextTick();

    const targetEl = document.getElementById('thermal-print-area');
    const currentWidth = paperWidth.value;

    injectDynamicPrintStyles(currentWidth);

    if (targetEl) {
        printViaIsolatedIframe(targetEl, currentWidth);
    } else {
        window.print();
    }
};

const copyOrderCode = async () => {
    if (!props.order?.order_code) return;
    try {
        await navigator.clipboard.writeText(props.order.order_code);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch (e) {
        console.error('Gagal menyalin kode:', e);
    }
};

// Pastikan guard role dan sinkronisasi barcode saat modal dibuka
watch(
    () => [props.isOpen, props.order, printMode.value, paperWidth.value, canPrintReceipt.value],
    () => {
        if (props.isOpen) {
            if (!canPrintReceipt.value) {
                printMode.value = 'shelf_tag';
            }
            renderBarcodes();
            injectDynamicPrintStyles(paperWidth.value);
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    const dynamicStyle = document.getElementById('spatoe-thermal-dynamic-print-style');
    if (dynamicStyle) dynamicStyle.remove();
    const iframe = document.getElementById('thermal-print-iframe');
    if (iframe) iframe.remove();
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen && order"
            class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto"
            tabindex="-1"
            @keydown.esc="emit('close')"
        >
            <!-- Backdrop Gelap (no-print) -->
            <div
                class="fixed inset-0 bg-neutral-950/80 backdrop-blur-xs transition-opacity no-print"
                @click="emit('close')"
            />

            <!-- Modal Window -->
            <div
                class="relative w-full max-w-2xl bg-neutral-900 text-white rounded-3xl shadow-2xl border border-neutral-800 z-10 flex flex-col max-h-[94vh] overflow-hidden transition-all"
            >
                <!-- Toolbar Header (no-print) -->
                <div class="px-5 py-4 border-b border-neutral-800 flex items-center justify-between no-print bg-neutral-900/90">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#6B0B23] text-white flex items-center justify-center font-bold shadow-xs">
                            <Printer class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-white leading-tight">
                                {{ canPrintReceipt ? 'Cetak Thermal Kasir & Label Tag' : 'Cetak Label Tag Rak Fisik' }}
                            </h3>
                            <p class="text-[11px] text-neutral-400 font-mono">
                                Nota: {{ order.order_code }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="p-2 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors"
                        title="Tutup Modal (Esc)"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Control Bar: Mode Toggle & Paper Width (no-print) -->
                <div class="px-5 py-3 border-b border-neutral-800 bg-neutral-950/70 flex flex-wrap items-center justify-between gap-3 no-print text-xs">
                    <!-- Tab Mode: Struk Kasir vs Label Tag Rak -->
                    <!-- Sembunyikan tab Struk Nota Kasir jika role adalah technician -->
                    <div v-if="canPrintReceipt" class="inline-flex bg-neutral-900 p-1 rounded-xl border border-neutral-800">
                        <button
                            type="button"
                            @click="printMode = 'receipt'"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition-all',
                                printMode === 'receipt'
                                    ? 'bg-[#6B0B23] text-white shadow-xs'
                                    : 'text-neutral-400 hover:text-neutral-200'
                            ]"
                        >
                            <Receipt class="w-3.5 h-3.5" />
                            <span>Struk Nota Kasir</span>
                        </button>
                        <button
                            type="button"
                            @click="printMode = 'shelf_tag'"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition-all',
                                printMode === 'shelf_tag'
                                    ? 'bg-[#6B0B23] text-white shadow-xs'
                                    : 'text-neutral-400 hover:text-neutral-200'
                            ]"
                        >
                            <Tag class="w-3.5 h-3.5" />
                            <span>Label Tag Rak Fisik</span>
                        </button>
                    </div>

                    <!-- Badge Khusus Teknisi (Hanya Label Tag Rak) -->
                    <div v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-neutral-900 border border-neutral-800 text-neutral-300 font-bold text-xs">
                        <Tag class="w-3.5 h-3.5 text-[#6B0B23]" />
                        <span>Label Tag Rak Fisik (Workshop)</span>
                    </div>

                    <!-- Pilihan Lebar Kertas: 58mm vs 80mm -->
                    <div class="flex items-center gap-2">
                        <span class="text-neutral-400 text-[11px] flex items-center gap-1">
                            <Sliders class="w-3 h-3" />
                            Lebar Kertas:
                        </span>
                        <div class="inline-flex bg-neutral-900 p-0.5 rounded-lg border border-neutral-800 font-mono text-[11px] font-bold">
                            <button
                                type="button"
                                @click="paperWidth = '58mm'"
                                :class="[
                                    'px-2.5 py-1 rounded-md transition-all',
                                    paperWidth === '58mm'
                                        ? 'bg-white text-neutral-950 shadow-xs'
                                        : 'text-neutral-400 hover:text-white'
                                ]"
                            >
                                58mm
                            </button>
                            <button
                                type="button"
                                @click="paperWidth = '80mm'"
                                :class="[
                                    'px-2.5 py-1 rounded-md transition-all',
                                    paperWidth === '80mm'
                                        ? 'bg-white text-neutral-950 shadow-xs'
                                        : 'text-neutral-400 hover:text-white'
                                ]"
                            >
                                80mm
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Area (Scrollable Screen Preview) -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 flex justify-center bg-neutral-950/90 min-h-[350px]">
                    <!-- Lembar Cetak Thermal (#thermal-print-area) -->
                    <div
                        id="thermal-print-area"
                        :class="[
                            'bg-white text-black p-4 sm:p-5 shadow-2xl font-mono text-xs leading-tight transition-all duration-300 select-none print:shadow-none break-inside-avoid',
                            paperWidth === '58mm' ? 'w-[58mm] max-w-[270px]' : 'w-[80mm] max-w-[360px]'
                        ]"
                    >
                        <!-- ================= MODE 1: STRUK NOTA KASIR (Admin & Supervisor Only) ================= -->
                        <div v-if="printMode === 'receipt' && canPrintReceipt" class="space-y-2 break-inside-avoid">
                            <!-- Header Mini Brand -->
                            <div class="text-center space-y-0.5 mb-2">
                                <h2 class="text-xl font-black italic tracking-wider">spatoe</h2>
                                <p class="text-[9.5px] font-bold uppercase tracking-wider text-black">
                                    Bengkel Cuci & Perawatan Sepatu
                                </p>
                                <p class="text-[9px] text-neutral-700">
                                    Jl. Gebang Wetan No. 18, Sukolilo, Surabaya
                                </p>
                                <p class="text-[9px] text-neutral-700">
                                    WA: 0812-3456-7890 | IG: @spatoe
                                </p>
                            </div>

                            <!-- Metadata Nota -->
                            <div class="border-t border-b border-black border-dashed py-1.5 my-2 text-[10px] space-y-0.5">
                                <div class="flex justify-between">
                                    <span>No. Nota:</span>
                                    <span class="font-bold">{{ order.order_code }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Waktu:</span>
                                    <span>{{ order.created_at || 'Hari ini' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pelanggan:</span>
                                    <span class="font-bold truncate max-w-[130px] text-right">{{ customerName }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>No. WhatsApp:</span>
                                    <span>{{ customerPhone }}</span>
                                </div>
                            </div>

                            <!-- Barcode CODE128 (Order Code) -->
                            <div class="text-center my-2">
                                <svg class="receipt-barcode-svg w-full max-h-16 mx-auto"></svg>
                            </div>

                            <!-- Tabel Daftar Item Sepatu -->
                            <div class="border-t border-b border-black border-dashed py-1.5 my-2 text-[10px]">
                                <div class="flex justify-between font-black border-b border-black border-dotted pb-1 mb-1">
                                    <span>ITEM / PAKET</span>
                                    <span>HARGA</span>
                                </div>

                                <div
                                    v-for="(item, idx) in orderItems"
                                    :key="item.id || idx"
                                    class="py-1 border-b border-neutral-300 border-dotted last:border-0"
                                >
                                    <div class="flex justify-between font-bold">
                                        <span class="truncate max-w-[150px]">
                                            [{{ item.item_tag_code }}] {{ item.shoe_brand_model }}
                                        </span>
                                        <span class="shrink-0">
                                            {{ formatRupiah(item.service?.base_price || item.price || 0) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-[9px] text-neutral-700">
                                        <span>
                                            {{ item.service?.service_name || item.service_name || 'Treatment' }}
                                            (Rak: {{ item.storage_shelf_number || item.shelf_number || '-' }})
                                        </span>
                                    </div>
                                    <div v-if="item.defect_notes" class="text-[8.5px] italic text-neutral-700 mt-0.5">
                                        *Cacat: {{ item.defect_notes }}
                                    </div>
                                </div>
                            </div>

                            <!-- Rincian Total Tagihan & Badge Status -->
                            <div class="text-[10px] space-y-1 my-2">
                                <div class="flex justify-between text-xs font-black">
                                    <span>TOTAL TAGIHAN:</span>
                                    <span>{{ formatRupiah(order.final_amount || order.total_amount || 0) }}</span>
                                </div>
                                <div class="flex justify-between text-[9px]">
                                    <span>Metode Bayar:</span>
                                    <span class="font-bold">{{ order.payment_method || 'QRIS' }}</span>
                                </div>
                                <div class="text-center pt-2">
                                    <span class="inline-block border-2 border-black font-black px-3 py-0.5 text-xs tracking-wider">
                                        {{ isPaid ? '[ LUNAS / PAID ]' : '[ BELUM LUNAS ]' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Footer Tautan Pelacakan Realtime -->
                            <div class="border-t border-black border-dashed pt-2 mt-2 text-center text-[9px] text-neutral-700 space-y-0.5">
                                <p class="font-bold text-black">Pantau status cuci sepatu Anda:</p>
                                <p class="font-bold font-mono text-black text-[10px]">spatoe.com/tracking</p>
                                <p class="text-[8px] pt-1 leading-tight text-neutral-600">
                                    Simpan struk nota ini sebagai bukti sah pengambilan sepatu di bengkel Spatoe.
                                </p>
                                <p class="font-black pt-1 text-black">*** TERIMA KASIH ***</p>
                            </div>
                        </div>

                        <!-- ================= MODE 2: LABEL TAG RAK FISIK ================= -->
                        <div v-else class="space-y-6">
                            <div
                                v-for="(item, idx) in orderItems"
                                :key="item.id || idx"
                                class="break-after-page break-inside-avoid pb-5 mb-5 border-b-2 border-dashed border-neutral-400 last:border-0 last:pb-0 last:mb-0"
                            >
                                <!-- Header Mini Label -->
                                <div class="text-center pb-1 border-b border-black border-dotted">
                                    <h3 class="text-xs font-black tracking-widest uppercase">
                                        SPATOE WORKSHOP TAG
                                    </h3>
                                    <p class="text-[9px]">
                                        Nota: <strong>{{ order.order_code }}</strong> (Sepatu {{ idx + 1 }}/{{ orderItems.length }})
                                    </p>
                                </div>

                                <!-- Nomor Tag Fisik Ekstra Besar -->
                                <div class="border-2 border-black rounded text-center my-2 py-1.5 bg-neutral-50">
                                    <span class="text-[8.5px] uppercase font-bold text-neutral-500 block">
                                        NOMOR TAG FISIK
                                    </span>
                                    <span class="text-3xl sm:text-4xl font-black font-mono tracking-tight block text-black">
                                        {{ item.item_tag_code }}
                                    </span>
                                </div>

                                <!-- Barcode CODE128 Tag -->
                                <div class="text-center my-1.5">
                                    <svg
                                        class="tag-barcode-svg w-full max-h-16 mx-auto"
                                        :data-barcode="item.item_tag_code"
                                    ></svg>
                                </div>

                                <!-- Blok Penanda Hitam Tebal Lokasi Rak Fisik -->
                                <div class="bg-black text-white text-center py-2 px-2 rounded my-2 font-mono font-black text-sm sm:text-base tracking-widest">
                                    LOKASI RAK: {{ (item.storage_shelf_number || item.shelf_number || 'A-01').toUpperCase() }}
                                </div>

                                <!-- Spesifikasi Sepatu -->
                                <div class="border border-black p-2 rounded text-[10px] space-y-1 my-2 bg-neutral-50/50">
                                    <div class="flex justify-between border-b border-neutral-200 pb-0.5">
                                        <span class="font-bold">Merk & Model:</span>
                                        <span class="font-semibold text-right truncate max-w-[130px]">
                                            {{ item.shoe_brand_model }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-neutral-200 pb-0.5">
                                        <span class="font-bold">Material / Warna:</span>
                                        <span>{{ [item.shoe_material, item.shoe_color].filter(Boolean).join(' • ') || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-neutral-200 pb-0.5">
                                        <span class="font-bold">Layanan:</span>
                                        <span class="font-bold">
                                            {{ item.service?.service_name || item.service_name || 'Treatment Standar' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold">Customer:</span>
                                        <span>{{ customerName }} ({{ customerPhone }})</span>
                                    </div>
                                </div>

                                <!-- Catatan Cacat Bawaan (Defect Notes) -->
                                <div
                                    v-if="item.defect_notes"
                                    class="border-2 border-black p-1.5 rounded text-[9.5px] font-bold bg-neutral-100 space-y-0.5"
                                >
                                    <p class="uppercase text-black underline tracking-wider font-black">
                                        PERHATIAN DEFECT AWAL:
                                    </p>
                                    <p class="font-mono text-neutral-800 leading-snug">
                                        {{ item.defect_notes }}
                                    </p>
                                </div>
                                <div v-else class="text-center text-[9px] text-neutral-500 italic py-0.5">
                                    Kondisi fisik normal saat intake.
                                </div>

                                <!-- Footer Tag -->
                                <div class="text-center text-[8px] text-neutral-500 pt-1.5 border-t border-black border-dotted mt-2">
                                    Spatoe Workshop • {{ order.created_at || 'Hari ini' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions Toolbar (no-print) -->
                <div class="px-5 py-3.5 border-t border-neutral-800 bg-neutral-900 flex flex-wrap items-center justify-between gap-3 no-print">
                    <button
                        type="button"
                        @click="copyOrderCode"
                        class="inline-flex items-center gap-1.5 text-xs text-neutral-400 hover:text-white transition-colors font-mono"
                    >
                        <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-400" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ isCopied ? 'Kode Disalin!' : 'Salin Kode Nota' }}</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="handlePrint"
                            class="inline-flex items-center gap-2 bg-[#6B0B23] hover:bg-[#54081B] text-white px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-md transition-all cursor-pointer"
                        >
                            <Printer class="w-4 h-4" />
                            <span>Cetak {{ printMode === 'receipt' && canPrintReceipt ? 'Struk Kasir' : 'Label Tag' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
/* Fallback CSS Print Optimization untuk Printer Termal USB / Bluetooth (POS 58mm & 80mm) */
@media print {
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    body * {
        visibility: hidden !important;
    }

    .no-print,
    .no-print * {
        display: none !important;
    }

    #thermal-print-area,
    #thermal-print-area * {
        visibility: visible !important;
    }

    #thermal-print-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 2mm 3mm !important;
        box-shadow: none !important;
        background: #ffffff !important;
        color: #000000 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .break-after-page {
        page-break-after: always !important;
        break-after: page !important;
    }

    .break-inside-avoid {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
