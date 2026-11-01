<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type DashboardStats = {
    omzet_hari_ini: number;
    persentase_omzet: number | null;
    transaksi_hari_ini: number;
    transaksi_dibatalkan_hari_ini: number;
    produk_aktif: number;
    stok_menipis: number;
    klaim_menunggu: number;
};

type GrafikItem = {
    label: string;
    tanggal: string;
    omzet: number;
};

type ProdukTerlaris = {
    id: number;
    nama: string;
    jumlah: number;
    satuan: string;
};

type StokMenipis = {
    id: number;
    nama: string;
    kategori: string;
    stok: number;
    satuan: string;
};

type TransaksiItem = {
    id: number;
    invoice: string;
    kasir: string;
    metode: string;
    total: number;
    status: string;
    jam: string | null;
};

type KlaimItem = {
    id: number;
    no_garansi: string;
    nama_produk: string;
    nama_pelanggan: string;
    keluhan: string;
    status: string;
    tanggal: string | null;
};

const props = defineProps<{
    tanggalHariIni: string;
    stats: DashboardStats;
    grafikOmzet: GrafikItem[];
    produkTerlaris: ProdukTerlaris[];
    daftarStokMenipis: StokMenipis[];
    transaksiTerbaru: TransaksiItem[];
    klaimMenunggu: KlaimItem[];
}>();

// Sapaan berdasarkan waktu lokal perangkat.
const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 18) return 'Selamat sore';

    return 'Selamat malam';
});

// Omzet terbesar untuk skala grafik.
const maxOmzet = computed(() =>
    Math.max(
        ...props.grafikOmzet.map((item) => item.omzet),
        1,
    ),
);

// Penjualan tertinggi untuk skala produk terlaris.
const maxProdukTerlaris = computed(() =>
    Math.max(
        ...props.produkTerlaris.map((item) => item.jumlah),
        1,
    ),
);

// Format Rupiah.
function rupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

// Format angka grafik menjadi ringkas.
function formatRingkas(value: number): string {
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toLocaleString('id-ID', {
            maximumFractionDigits: 2,
        })} jt`;
    }

    if (value >= 1_000) {
        return `${(value / 1_000).toLocaleString('id-ID', {
            maximumFractionDigits: 1,
        })} rb`;
    }

    return value.toLocaleString('id-ID');
}

// Tinggi batang grafik.
function tinggiBatang(value: number): string {
    if (value <= 0) return '3%';

    return `${Math.max((value / maxOmzet.value) * 100, 8)}%`;
}

// Lebar progress produk terlaris.
function lebarProgress(value: number): string {
    return `${Math.max(
        (value / maxProdukTerlaris.value) * 100,
        3,
    )}%`;
}

// Label status klaim.
function labelStatusKlaim(status: string): string {
    const labels: Record<string, string> = {
        diajukan: 'Diajukan',
        diverifikasi: 'Diverifikasi',
        disetujui: 'Disetujui',
        ditolak: 'Ditolak',
        selesai: 'Selesai',
    };

    return labels[status] ?? status;
}

// Label status transaksi.
function labelStatusTransaksi(status: string): string {
    return status === 'lunas' ? 'Lunas' : 'Dibatalkan';
}

// Label metode pembayaran.
function labelMetode(metode: string): string {
    return metode === 'qris' ? 'QRIS' : 'Tunai';
}
</script>

<template>
    <Head title="Dashboard Admin" />

    <div class="space-y-6">

        <!-- HEADER DASHBOARD -->
        <section class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-medium text-slate-500">
                    {{ tanggalHariIni }}
                </p>

                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#08264F] sm:text-3xl">
                    {{ greeting }}, Admin
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Berikut ringkasan aktivitas toko hari ini.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="#ringkasan-penjualan"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-[#08264F] transition hover:border-[#08264F] hover:bg-slate-50"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15V9m4 6V5m4 10v-4"/>
                    </svg>

                    Lihat ringkasan
                </a>

                <Link
                    href="/admin/produk/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#FF6B16] px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" d="M12 5v14m-7-7h14"/>
                    </svg>

                    Tambah produk
                </Link>
            </div>
        </section>

        <!-- KARTU STATISTIK -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">

            <!-- OMZET -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        Omzet hari ini
                    </p>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-[#FF6B16]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="6" width="18" height="13" rx="2"/>
                            <path stroke-linecap="round" d="M3 10h18m-5 5h2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-3 text-2xl font-extrabold tracking-tight text-[#08264F] sm:text-3xl">
                    {{ rupiah(stats.omzet_hari_ini) }}
                </p>

                <p
                    v-if="stats.persentase_omzet !== null"
                    class="mt-2 text-xs font-semibold"
                    :class="stats.persentase_omzet >= 0 ? 'text-green-700' : 'text-red-600'"
                >
                    {{ stats.persentase_omzet >= 0 ? '▲' : '▼' }}
                    {{ Math.abs(stats.persentase_omzet) }}% dibanding kemarin
                </p>

                <p v-else class="mt-2 text-xs text-slate-400">
                    Belum ada pembanding omzet kemarin.
                </p>
            </div>

            <!-- TRANSAKSI -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        Transaksi hari ini
                    </p>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#08264F]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="5" y="3" width="14" height="18" rx="2"/>
                            <path stroke-linecap="round" d="M9 8h6m-6 4h6m-6 4h4"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-3 text-3xl font-extrabold text-[#08264F]">
                    {{ stats.transaksi_hari_ini }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    {{ stats.transaksi_dibatalkan_hari_ini }} dibatalkan (void)
                </p>
            </div>

            <!-- PRODUK -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        Produk aktif
                    </p>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-[#08264F]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linejoin="round" d="m12 3 9 5-9 5-9-5 9-5Z"/>
                            <path stroke-linejoin="round" d="M3 8v8l9 5 9-5V8M12 13v8"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-3 text-3xl font-extrabold text-[#08264F]">
                    {{ stats.produk_aktif }}
                </p>

                <Link
                    href="/admin/produk?status=stok_menipis"
                    class="mt-2 inline-flex text-xs font-semibold text-[#FF6B16] hover:underline"
                >
                    {{ stats.stok_menipis }} produk stok menipis
                </Link>
            </div>

            <!-- GARANSI -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        Klaim garansi
                    </p>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 20 6v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-3Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-3 text-3xl font-extrabold text-[#08264F]">
                    {{ stats.klaim_menunggu }}
                </p>

                <Link
                    href="/admin/klaim-garansi"
                    class="mt-2 inline-flex text-xs font-semibold text-blue-700 hover:underline"
                >
                    Menunggu tindakan admin
                </Link>
            </div>
        </section>

        <!-- GRAFIK DAN PRODUK TERLARIS -->
        <section id="ringkasan-penjualan" class="grid scroll-mt-24 grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.8fr)_minmax(280px,0.9fr)]">

            <!-- GRAFIK OMZET -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Omzet 7 Hari Terakhir
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Berdasarkan transaksi berstatus lunas.
                        </p>
                    </div>

                    <span class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-[11px] font-semibold text-blue-700">
                        7 hari terakhir
                    </span>
                </div>

                <div v-if="grafikOmzet.some((item) => item.omzet > 0)" class="mt-7">
                    <div class="flex h-52 items-end gap-2 border-b border-slate-200 px-1 sm:gap-3">
                        <div
                            v-for="hari in grafikOmzet"
                            :key="hari.tanggal"
                            class="flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                        >
                            <span class="mb-2 text-[10px] font-semibold text-slate-400 sm:text-xs">
                                {{ formatRingkas(hari.omzet) }}
                            </span>

                            <div class="flex h-36 w-full items-end justify-center">
                                <div
                                    class="w-full max-w-12 rounded-t-xl transition-all duration-300"
                                    :class="hari.tanggal === grafikOmzet[grafikOmzet.length - 1]?.tanggal
                                        ? 'bg-[#FF6B16]'
                                        : 'bg-[#E8EEF8]'"
                                    :style="{ height: tinggiBatang(hari.omzet) }"
                                    :title="`${hari.tanggal}: ${rupiah(hari.omzet)}`"
                                ></div>
                            </div>

                            <span class="mt-2 text-[10px] font-medium text-slate-500 sm:text-xs">
                                {{ hari.label }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-between text-[10px] text-slate-400">
                        <span>{{ grafikOmzet[0]?.tanggal }}</span>
                        <span>{{ grafikOmzet[grafikOmzet.length - 1]?.tanggal }}</span>
                    </div>
                </div>

                <div v-else class="flex h-56 flex-col items-center justify-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15V9m4 6V5m4 10v-4"/>
                        </svg>
                    </div>

                    <p class="mt-3 text-sm font-semibold text-slate-600">
                        Belum ada data penjualan
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Grafik akan terisi ketika ada transaksi lunas.
                    </p>
                </div>
            </div>

            <!-- PRODUK TERLARIS -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h3 class="text-lg font-bold text-[#08264F]">
                        Produk Terlaris
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Berdasarkan jumlah barang terjual selama 7 hari.
                    </p>
                </div>

                <div v-if="produkTerlaris.length > 0" class="mt-6 space-y-5">
                    <div
                        v-for="(item, index) in produkTerlaris"
                        :key="item.id"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="min-w-0 text-xs font-semibold leading-5 text-[#08264F]">
                                {{ index + 1 }}. {{ item.nama }}
                            </p>

                            <span class="shrink-0 text-xs text-slate-500">
                                {{ item.jumlah }} {{ item.satuan }}
                            </span>
                        </div>

                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-[#08264F] transition-all"
                                :style="{ width: lebarProgress(item.jumlah) }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex h-52 items-center justify-center text-center">
                    <p class="text-sm text-slate-400">
                        Belum ada produk terjual dalam 7 hari terakhir.
                    </p>
                </div>
            </div>
        </section>

        <!-- TRANSAKSI DAN STOK -->
        <section class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.8fr)_minmax(280px,0.9fr)]">

            <!-- TRANSAKSI TERBARU -->
            <div id="transaksi-terbaru" class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 p-5 sm:p-6">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Transaksi Terbaru
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Lima transaksi terakhir.
                        </p>
                    </div>

                    <Link
                        href="/kasir/riwayat"
                        class="shrink-0 text-xs font-semibold text-[#FF6B16] hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left">
                        <thead>
                            <tr class="border-y border-slate-100 bg-slate-50 text-[11px] font-semibold text-slate-500">
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-4 py-3">Kasir</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Jam</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in transaksiTerbaru"
                                :key="item.id"
                                class="transition hover:bg-slate-50"
                            >
                                <td class="px-5 py-3.5">
                                    <Link
                                        :href="`/kasir/struk/${encodeURIComponent(item.invoice)}`"
                                        class="text-xs font-semibold text-[#08264F] hover:text-[#FF6B16]"
                                    >
                                        {{ item.invoice }}
                                    </Link>
                                </td>

                                <td class="px-4 py-3.5 text-xs text-slate-700">
                                    {{ item.kasir }}
                                </td>

                                <td class="px-4 py-3.5">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                        {{ labelMetode(item.metode) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-right text-xs font-bold text-[#08264F]">
                                    {{ rupiah(item.total) }}
                                </td>

                                <td class="px-4 py-3.5">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[10px] font-bold"
                                        :class="item.status === 'lunas'
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700'"
                                    >
                                        {{ labelStatusTransaksi(item.status) }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-right text-xs text-slate-500">
                                    {{ item.jam ?? '—' }}
                                </td>
                            </tr>

                            <tr v-if="transaksiTerbaru.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- STOK MENIPIS -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Stok Menipis
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Ambang batas: 5 unit atau kurang.
                        </p>
                    </div>

                    <Link
                        href="/admin/produk?status=stok_menipis"
                        class="text-xs font-semibold text-[#FF6B16] hover:underline"
                    >
                        Lihat
                    </Link>
                </div>

                <div v-if="daftarStokMenipis.length > 0" class="mt-5 space-y-5">
                    <div
                        v-for="item in daftarStokMenipis"
                        :key="item.id"
                        class="flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-[#08264F]">
                                {{ item.nama }}
                            </p>

                            <p class="mt-1 text-[10px] text-slate-400">
                                {{ item.kategori }}
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold"
                            :class="item.stok === 0
                                ? 'bg-red-100 text-red-700'
                                : 'bg-amber-100 text-amber-700'"
                        >
                            {{ item.stok === 0 ? 'Habis' : `Sisa ${item.stok}` }}
                        </span>
                    </div>
                </div>

                <div v-else class="flex h-48 items-center justify-center text-center">
                    <div>
                        <svg class="mx-auto h-9 w-9 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                        </svg>

                        <p class="mt-3 text-sm font-semibold text-slate-600">
                            Stok dalam kondisi aman
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- KLAIM GARANSI MENUNGGU -->
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h3 class="text-lg font-bold text-[#08264F]">
                        Klaim Garansi Menunggu
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ stats.klaim_menunggu }} klaim masih membutuhkan tindakan admin.
                    </p>
                </div>

                <Link
                    href="/admin/klaim-garansi"
                    class="w-fit text-xs font-semibold text-[#FF6B16] hover:underline"
                >
                    Kelola klaim →
                </Link>
            </div>

            <div
                v-if="klaimMenunggu.length > 0"
                class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="item in klaimMenunggu"
                    :key="item.id"
                    class="rounded-xl border border-slate-100 bg-[#F4F7FB] p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="break-all text-[11px] font-semibold text-slate-500">
                            {{ item.no_garansi }}
                        </p>

                        <span
                            class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold"
                            :class="{
                                'bg-orange-100 text-orange-700': item.status === 'diajukan',
                                'bg-blue-100 text-blue-700': item.status === 'diverifikasi',
                                'bg-green-100 text-green-700': item.status === 'disetujui',
                            }"
                        >
                            {{ labelStatusKlaim(item.status) }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm font-bold text-[#08264F]">
                        {{ item.nama_produk }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ item.nama_pelanggan }}
                    </p>

                    <p class="mt-3 line-clamp-2 text-xs leading-5 text-slate-600">
                        "{{ item.keluhan }}"
                    </p>

                    <p class="mt-3 border-t border-slate-200 pt-2 text-[10px] text-slate-400">
                        {{ item.tanggal ?? 'Tanggal tidak tersedia' }}
                    </p>
                </div>
            </div>

            <div v-else class="mt-5 rounded-xl bg-green-50 px-5 py-8 text-center">
                <p class="text-sm font-semibold text-green-800">
                    Tidak ada klaim yang menunggu tindakan.
                </p>

                <p class="mt-1 text-xs text-green-700">
                    Semua pengajuan garansi sudah ditindaklanjuti.
                </p>
            </div>
        </section>
    </div>
</template>
