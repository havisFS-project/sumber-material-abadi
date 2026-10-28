
<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

type TransaksiItem = {
    id: number;
    no_invoice: string;
    tanggal: string | null;
    nama_pelanggan: string | null;
    nama_kasir: string;
    jumlah_detail: number;
    subtotal: number;
    total_diskon: number;
    total_bayar: number;
    metode_bayar: 'tunai' | 'qris';
    status: 'lunas' | 'dibatalkan';
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    transaksi: {
        data: TransaksiItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q: string;
    };
}>();

const search = ref(props.filters.q ?? '');

function rupiah(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function cariTransaksi() {
    router.get(
        '/kasir/riwayat',
        { q: search.value.trim() || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetPencarian() {
    search.value = '';

    router.get('/kasir/riwayat', {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function bukaHalaman(url: string | null) {
    if (!url) return;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

function labelHalaman(label: string) {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <div class="min-h-screen bg-[#F4F7FB] text-slate-800">
        <header class="border-b border-white/10 bg-[#08264F] text-white shadow-sm print:hidden">
            <div class="mx-auto flex max-w-[1500px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#FF6B16] font-black">
                        SMA
                    </div>

                    <div>
                        <h1 class="font-bold">Sumber Material Abadi</h1>
                        <p class="text-xs text-blue-200">Riwayat Penjualan</p>
                    </div>
                </div>

                <Link
                    href="/kasir"
                    class="rounded-lg border border-white/20 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/10"
                >
                    Kembali ke POS
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-6 lg:p-8">
            <section>
                <p class="text-sm font-medium text-[#FF6B16]">
                    PENJUALAN TOKO
                </p>

                <h2 class="mt-1 text-2xl font-bold text-[#08264F] sm:text-3xl">
                    Riwayat Transaksi
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Cari invoice dan lihat kembali transaksi yang sudah tercatat.
                </p>
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Transaksi Ditemukan
                    </p>

                    <p class="mt-3 text-3xl font-bold text-[#08264F]">
                        {{ transaksi.total }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Sesuai pencarian dan akses akun saat ini
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Halaman Aktif
                    </p>

                    <p class="mt-3 text-3xl font-bold text-[#FF6B16]">
                        {{ transaksi.current_page }}
                        <span class="text-base font-medium text-slate-400">
                            / {{ transaksi.last_page }}
                        </span>
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Maksimal 10 transaksi per halaman
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5 sm:p-6 print:hidden">
                    <form
                        class="flex flex-col gap-3 sm:flex-row"
                        @submit.prevent="cariTransaksi"
                    >
                        <input
                            v-model="search"
                            type="search"
                            aria-label="Cari nomor invoice atau pelanggan"
                            placeholder="Cari nomor invoice atau nama pelanggan..."
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                        />

                        <button
                            type="submit"
                            class="rounded-xl bg-[#08264F] px-5 py-3 text-sm font-semibold text-white hover:bg-[#10386C]"
                        >
                            Cari
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                            @click="resetPencarian"
                        >
                            Reset
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[950px] text-left">
                        <thead>
                            <tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-4 font-semibold">Invoice / Tanggal</th>
                                <th class="px-5 py-4 font-semibold">Pelanggan</th>
                                <th class="px-5 py-4 font-semibold">Kasir</th>
                                <th class="px-5 py-4 font-semibold">Pembayaran</th>
                                <th class="px-5 py-4 font-semibold">Total</th>
                                <th class="px-5 py-4 font-semibold">Status</th>
                                <th class="px-5 py-4 font-semibold">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in transaksi.data"
                                :key="item.id"
                                class="transition hover:bg-slate-50/70"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-[#08264F]">
                                        {{ item.no_invoice }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.tanggal ?? '—' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ item.nama_pelanggan || 'Pelanggan umum' }}
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ item.nama_kasir }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ item.metode_bayar.toUpperCase() }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 font-bold text-[#08264F]">
                                    {{ rupiah(item.total_bayar) }}
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            item.status === 'lunas'
                                                ? 'bg-green-50 text-green-700'
                                                : 'bg-red-50 text-red-700'
                                        "
                                    >
                                        {{ item.status === 'lunas' ? 'Lunas' : 'Dibatalkan' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <Link
                                        :href="`/kasir/struk/${encodeURIComponent(item.no_invoice)}`"
                                        class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#08264F] transition hover:border-[#FF6B16] hover:text-[#FF6B16]"
                                    >
                                        Lihat Struk
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="transaksi.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="font-semibold text-slate-700">
                                        Belum ada transaksi yang cocok
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Coba kata kunci berbeda atau periksa riwayat transaksi lain.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
                    <p class="text-sm text-slate-500">
                        Halaman {{ transaksi.current_page }} dari {{ transaksi.last_page }}
                    </p>

                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="link in transaksi.links"
                            :key="link.label"
                            type="button"
                            :disabled="!link.url"
                            class="min-w-9 rounded-lg border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                            :class="
                                link.active
                                    ? 'border-[#08264F] bg-[#08264F] font-semibold text-white'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            "
                            @click="bukaHalaman(link.url)"
                        >
                            {{ labelHalaman(link.label) }}
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
