
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
    alasan_batal: string | null;
    can_void: boolean;
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

const transaksiDipilih = ref<TransaksiItem | null>(null);
const alasanBatal = ref('');
const errorBatal = ref('');
const sedangMembatalkan = ref(false);

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

function bukaDialogBatal(item: TransaksiItem) {
    if (!item.can_void || item.status !== 'lunas') return;

    transaksiDipilih.value = item;
    alasanBatal.value = '';
    errorBatal.value = '';
}

function tutupDialogBatal() {
    if (sedangMembatalkan.value) return;

    transaksiDipilih.value = null;
    alasanBatal.value = '';
    errorBatal.value = '';
}

function konfirmasiBatal() {
    const item = transaksiDipilih.value;
    const alasan = alasanBatal.value.trim();

    if (!item) return;

    if (alasan.length < 5) {
        errorBatal.value = 'Alasan pembatalan minimal 5 karakter.';
        return;
    }

    if (alasan.length > 2000) {
        errorBatal.value = 'Alasan pembatalan maksimal 2.000 karakter.';
        return;
    }

    errorBatal.value = '';
    sedangMembatalkan.value = true;

    router.patch(
        `/admin/transaksi/${item.id}/void`,
        {
            alasan_batal: alasan,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                transaksiDipilih.value = null;
                alasanBatal.value = '';
                errorBatal.value = '';
            },

            onError: (errors) => {
                errorBatal.value =
                    errors.alasan_batal ??
                    'Pembatalan gagal diproses. Silakan periksa kembali.';
            },

            onFinish: () => {
                sedangMembatalkan.value = false;
            },
        },
    );
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
                    Cari invoice, lihat struk, dan kelola pembatalan transaksi.
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
                    <table class="w-full min-w-[1100px] text-left">
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

                                    <p
                                        v-if="item.status === 'dibatalkan' && item.alasan_batal"
                                        class="mt-2 max-w-[220px] text-xs leading-relaxed text-red-600"
                                    >
                                        Alasan: {{ item.alasan_batal }}
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            :href="`/kasir/struk/${encodeURIComponent(item.no_invoice)}`"
                                            class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#08264F] transition hover:border-[#FF6B16] hover:text-[#FF6B16]"
                                        >
                                            Lihat Struk
                                        </Link>

                                        <button
                                            v-if="item.can_void && item.status === 'lunas'"
                                            type="button"
                                            class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                                            @click="bukaDialogBatal(item)"
                                        >
                                            Batalkan
                                        </button>
                                    </div>
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

        <!-- Dialog Konfirmasi Pembatalan -->
        <div
            v-if="transaksiDipilih"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
            @click.self="tutupDialogBatal"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path d="M10.3 3.9 1.8 18.1A2 2 0 0 0 3.5 21h17a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-[#08264F]">
                            Batalkan Transaksi?
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Tindakan ini akan membatalkan transaksi dan mengembalikan stok produk.
                        </p>
                    </div>

                    <button
                        type="button"
                        :disabled="sedangMembatalkan"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50"
                        aria-label="Tutup dialog"
                        @click="tutupDialogBatal"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Invoice
                        </p>

                        <p class="mt-1 font-bold text-[#08264F]">
                            {{ transaksiDipilih.no_invoice }}
                        </p>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-500">
                                Total transaksi
                            </span>

                            <span class="font-bold text-[#08264F]">
                                {{ rupiah(transaksiDipilih.total_bayar) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label
                            for="alasan-batal"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Alasan Pembatalan
                            <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            id="alasan-batal"
                            v-model="alasanBatal"
                            rows="4"
                            maxlength="2000"
                            placeholder="Contoh: Terjadi kesalahan input produk pada transaksi..."
                            class="w-full resize-y rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            :disabled="sedangMembatalkan"
                            @input="errorBatal = ''"
                        />

                        <div class="mt-1 flex items-center justify-between gap-3">
                            <p class="text-xs text-slate-400">
                                Minimal 5 karakter.
                            </p>

                            <p class="text-xs text-slate-400">
                                {{ alasanBatal.length }}/2000
                            </p>
                        </div>

                        <p
                            v-if="errorBatal"
                            class="mt-2 text-sm font-medium text-red-600"
                        >
                            {{ errorBatal }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800">
                            Perhatian
                        </p>

                        <p class="mt-1 text-xs leading-relaxed text-amber-700">
                            Garansi dari transaksi ini akan dinyatakan tidak berlaku.
                            Transaksi yang memiliki klaim garansi berjalan tidak dapat dibatalkan.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            :disabled="sedangMembatalkan"
                            class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="tutupDialogBatal"
                        >
                            Kembali
                        </button>

                        <button
                            type="button"
                            :disabled="sedangMembatalkan || alasanBatal.trim().length < 5"
                            class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="konfirmasiBatal"
                        >
                            {{ sedangMembatalkan ? 'Memproses...' : 'Ya, Batalkan Transaksi' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
