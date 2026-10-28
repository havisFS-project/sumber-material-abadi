
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type GaransiItem = {
    no_garansi: string;
    tanggal_mulai: string | null;
    tanggal_berakhir: string | null;
} | null;

type DetailItem = {
    id: number;
    nama_produk: string;
    harga_satuan: number;
    diskon_satuan: number;
    jumlah: number;
    subtotal: number;
    garansi: GaransiItem;
};

type TransaksiItem = {
    id: number;
    no_invoice: string;
    tanggal: string | null;
    nama_pelanggan: string | null;
    no_hp_pelanggan: string | null;
    nama_kasir: string;
    subtotal: number;
    total_diskon: number;
    total_bayar: number;
    metode_bayar: 'tunai' | 'qris';
    uang_diterima: number | null;
    kembalian: number | null;
    status: 'lunas' | 'dibatalkan';
    detail: DetailItem[];
};

defineProps<{
    transaksi: TransaksiItem;
}>();

function rupiah(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function cetakStruk() {
    window.print();
}
</script>

<template>
    <Head :title="`Struk ${transaksi.no_invoice}`" />

    <div class="min-h-screen bg-[#F4F7FB] px-4 py-6 text-slate-800 sm:px-6 lg:py-10">
        <div class="mx-auto max-w-3xl">
            <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
                <Link
                    href="/kasir/riwayat"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-[#08264F] transition hover:bg-slate-50"
                >
                    ← Kembali ke Riwayat
                </Link>

                <button
                    type="button"
                    class="rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                    @click="cetakStruk"
                >
                    Cetak Struk
                </button>
            </div>

            <article class="receipt-paper overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-dashed border-slate-300 px-6 py-7 text-center sm:px-10">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FF6B16] text-lg font-black text-white">
                        SMA
                    </div>

                    <h1 class="mt-4 text-xl font-bold text-[#08264F]">
                        Sumber Material Abadi
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Bukti Transaksi Penjualan
                    </p>

                    <p
                        v-if="transaksi.status === 'dibatalkan'"
                        class="mt-4 inline-block rounded-full bg-red-50 px-3 py-1 text-sm font-bold text-red-700"
                    >
                        TRANSAKSI DIBATALKAN
                    </p>
                </header>

                <section class="grid grid-cols-1 gap-5 border-b border-dashed border-slate-300 px-6 py-6 text-sm sm:grid-cols-2 sm:px-10">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-slate-500">Nomor Invoice</p>
                            <p class="mt-1 break-all font-bold text-[#08264F]">
                                {{ transaksi.no_invoice }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Tanggal Transaksi</p>
                            <p class="mt-1 font-medium">
                                {{ transaksi.tanggal ?? '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Kasir</p>
                            <p class="mt-1 font-medium">
                                {{ transaksi.nama_kasir }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-slate-500">Pelanggan</p>
                            <p class="mt-1 font-medium">
                                {{ transaksi.nama_pelanggan || 'Pelanggan umum' }}
                            </p>
                        </div>

                        <div v-if="transaksi.no_hp_pelanggan">
                            <p class="text-xs text-slate-500">Nomor HP</p>
                            <p class="mt-1 font-medium">
                                {{ transaksi.no_hp_pelanggan }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Metode Pembayaran</p>
                            <p class="mt-1 font-medium">
                                {{
                                    transaksi.metode_bayar === 'qris'
                                        ? 'QRIS (Simulasi)'
                                        : 'Tunai'
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="px-6 py-6 sm:px-10">
                    <h2 class="mb-4 font-bold text-[#08264F]">
                        Rincian Barang
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-3 pr-3 font-semibold">Produk</th>
                                    <th class="px-2 py-3 text-center font-semibold">Qty</th>
                                    <th class="px-2 py-3 text-right font-semibold">Harga</th>
                                    <th class="py-3 pl-2 text-right font-semibold">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="item in transaksi.detail"
                                    :key="item.id"
                                >
                                    <td class="py-4 pr-3 align-top">
                                        <p class="font-semibold text-slate-800">
                                            {{ item.nama_produk }}
                                        </p>

                                        <p
                                            v-if="item.diskon_satuan > 0"
                                            class="mt-1 text-xs text-green-700"
                                        >
                                            Diskon/unit: {{ rupiah(item.diskon_satuan) }}
                                        </p>

                                        <div v-if="item.garansi" class="mt-2 text-xs leading-5 text-slate-500">
                                            <p>
                                                Garansi: {{ item.garansi.no_garansi }}
                                            </p>
                                            <p>
                                                Berlaku:
                                                {{ item.garansi.tanggal_mulai ?? '—' }}
                                                –
                                                {{ item.garansi.tanggal_berakhir ?? '—' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-2 py-4 text-center align-top">
                                        {{ item.jumlah }}
                                    </td>

                                    <td class="px-2 py-4 text-right align-top whitespace-nowrap">
                                        {{ rupiah(item.harga_satuan) }}
                                    </td>

                                    <td class="py-4 pl-2 text-right align-top font-semibold whitespace-nowrap">
                                        {{ rupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="border-t border-dashed border-slate-300 px-6 py-6 sm:px-10">
                    <div class="ml-auto max-w-sm space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Subtotal harga awal</span>
                            <span class="text-right font-medium">
                                {{ rupiah(transaksi.subtotal) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Total diskon</span>
                            <span class="text-right font-medium text-green-700">
                                − {{ rupiah(transaksi.total_diskon) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-4 text-base">
                            <span class="font-bold text-[#08264F]">
                                Total Bayar
                            </span>
                            <span class="text-right text-xl font-bold text-[#08264F]">
                                {{ rupiah(transaksi.total_bayar) }}
                            </span>
                        </div>

                        <template v-if="transaksi.metode_bayar === 'tunai'">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">Uang diterima</span>
                                <span class="text-right font-medium">
                                    {{
                                        transaksi.uang_diterima === null
                                            ? '—'
                                            : rupiah(transaksi.uang_diterima)
                                    }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">Kembalian</span>
                                <span class="text-right font-medium">
                                    {{
                                        transaksi.kembalian === null
                                            ? '—'
                                            : rupiah(transaksi.kembalian)
                                    }}
                                </span>
                            </div>
                        </template>
                    </div>
                </section>

                <footer class="border-t border-dashed border-slate-300 bg-slate-50 px-6 py-7 text-center sm:px-10">
                    <p class="font-semibold text-[#08264F]">
                        Terima kasih telah berbelanja!
                    </p>

                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Simpan bukti transaksi ini untuk keperluan pemeriksaan
                        atau layanan garansi jika tersedia.
                    </p>
                </footer>
            </article>

            <p class="no-print mt-4 text-center text-xs text-slate-400">
                Pilih Cetak Struk untuk mencetak atau menyimpan struk sebagai PDF dari browser.
            </p>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 12mm;
    }

    body {
        background: white !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .no-print {
        display: none !important;
    }

    .receipt-paper {
        max-width: none !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
}
</style>
