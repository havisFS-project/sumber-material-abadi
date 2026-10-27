
<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type Kategori = {
    id: number;
    nama: string;
};

type ProdukItem = {
    id: number;
    kode: string;
    nama: string;
    satuan: string;
    kategori: string | null;
    harga_jual: number;
    harga_akhir: number;
    potongan: number;
    diskon_aktif: boolean;
    stok: number;
    is_active: boolean;
    punya_garansi: boolean;
    masa_garansi_bulan: number | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    produk: {
        data: ProdukItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    kategoris: Kategori[];
    stats: {
        total: number;
        aktif: number;
        stok_menipis: number;
        nonaktif: number;
    };
    filters: {
        q: string;
        kategori: string | number | null;
        status: string;
    };
}>();

const search = ref(props.filters.q ?? '');
const kategori = ref(
    props.filters.kategori == null
        ? ''
        : String(props.filters.kategori),
);
const status = ref(props.filters.status ?? 'semua');

const showingFrom = computed(() => {
    if (props.produk.total === 0) return 0;

    return (props.produk.current_page - 1) * 10 + 1;
});

const showingTo = computed(() => {
    return Math.min(props.produk.current_page * 10, props.produk.total);
});

function applyFilters() {
    router.get(
        '/admin/produk',
        {
            q: search.value.trim() || undefined,
            kategori: kategori.value || undefined,
            status: status.value === 'semua' ? undefined : status.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters() {
    search.value = '';
    kategori.value = '';
    status.value = 'semua';

    router.get(
        '/admin/produk',
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function goToPage(url: string | null) {
    if (!url) return;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

function changeStatus(item: ProdukItem) {
    const nextStatus = !item.is_active;

    const action = nextStatus
        ? 'mengaktifkan'
        : 'menonaktifkan';

    const explanation = nextStatus
        ? 'Produk akan kembali berstatus aktif.'
        : 'Produk tidak lagi berstatus aktif, tetapi data dan riwayat transaksi tetap dipertahankan.';

    const confirmed = window.confirm(
        `Yakin ingin ${action} produk "${item.nama}"?\n\n${explanation}`,
    );

    if (!confirmed) {
        return;
    }

    router.patch(
        `/admin/produk/${item.id}/status`,
        {
            is_active: nextStatus,
        },
        {
            preserveScroll: true,
        },
    );
}

function rupiah(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Data Produk" />

    <div class="space-y-6">
        <section
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    INVENTARIS TOKO
                </p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#08264F] sm:text-3xl">
                    Data Produk
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    Kelola produk, harga, diskon, dan stok barang toko.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600"
                >
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    Data tersimpan di database
                </span>

                <Link
                    href="/admin/produk/create"
                    class="rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                    >
                    + Tambah Produk
                </Link>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Total Produk
                    </p>
                    <span class="rounded-xl bg-blue-50 px-3 py-2 text-lg text-[#08264F]">
                        ▤
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-[#08264F]">
                    {{ stats.total }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Semua produk terdaftar
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Produk Aktif
                    </p>
                    <span class="rounded-xl bg-green-50 px-3 py-2 text-lg text-green-700">
                        ✓
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-green-700">
                    {{ stats.aktif }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Tersedia untuk dijual
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Stok Menipis
                    </p>
                    <span class="rounded-xl bg-orange-50 px-3 py-2 text-lg text-[#FF6B16]">
                        !
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-[#FF6B16]">
                    {{ stats.stok_menipis }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Stok 5 unit atau kurang
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Produk Nonaktif
                    </p>
                    <span class="rounded-xl bg-slate-100 px-3 py-2 text-lg text-slate-500">
                        —
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-slate-700">
                    {{ stats.nonaktif }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Tidak aktif untuk dijual
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5 sm:p-6">
                <div
                    class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
                >
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Daftar Produk
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Cari barang dan periksa harga serta ketersediaan stok.
                        </p>
                    </div>

                    <span class="text-sm text-slate-500">
                        {{ produk.total }} produk
                    </span>
                </div>

                <form
                    class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_220px_200px_auto_auto]"
                    @submit.prevent="applyFilters"
                >
                    <label class="relative block">
                        <span class="sr-only">Cari produk</span>
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            ⌕
                        </span>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari nama atau kode produk..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-9 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                        />
                    </label>

                    <select
                        v-model="kategori"
                        aria-label="Filter kategori"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="">Semua kategori</option>
                        <option
                            v-for="item in kategoris"
                            :key="item.id"
                            :value="String(item.id)"
                        >
                            {{ item.nama }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        aria-label="Filter status produk"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="semua">Semua status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="stok_menipis">Stok menipis</option>
                    </select>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#08264F] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10386C]"
                    >
                        Cari
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 font-semibold">Produk</th>
                            <th class="px-5 py-4 font-semibold">Kategori</th>
                            <th class="px-5 py-4 font-semibold">Harga Jual</th>
                            <th class="px-5 py-4 font-semibold">Stok</th>
                            <th class="px-5 py-4 font-semibold">Garansi</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="item in produk.data"
                            :key="item.id"
                            class="transition hover:bg-slate-50/70"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-[#08264F]">
                                        {{ item.nama.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">
                                            {{ item.nama }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ item.kode }} · per {{ item.satuan }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ item.kategori ?? 'Tanpa kategori' }}
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#08264F]">
                                    {{ rupiah(item.harga_akhir) }}
                                </p>
                                <div
                                    v-if="item.diskon_aktif && item.potongan > 0"
                                    class="mt-1 flex flex-wrap items-center gap-2"
                                >
                                    <span class="text-xs text-slate-400 line-through">
                                        {{ rupiah(item.harga_jual) }}
                                    </span>
                                    <span class="rounded-md bg-orange-50 px-1.5 py-0.5 text-xs font-semibold text-[#D95408]">
                                        Hemat {{ rupiah(item.potongan) }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-lg px-2.5 py-1 text-sm font-semibold"
                                    :class="
                                        item.stok <= 5
                                            ? 'bg-orange-50 text-orange-700'
                                            : 'bg-slate-100 text-slate-700'
                                    "
                                >
                                    {{ item.stok }} {{ item.satuan }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    v-if="item.punya_garansi"
                                    class="text-sm font-medium text-green-700"
                                >
                                    {{ item.masa_garansi_bulan ?? 0 }} bulan
                                </span>
                                <span v-else class="text-sm text-slate-400">
                                    —
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        item.is_active
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            item.is_active
                                                ? 'bg-green-500'
                                                : 'bg-slate-400'
                                        "
                                    ></span>
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/admin/produk/${item.id}/edit`"
                                        class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#08264F] transition hover:border-[#FF6B16] hover:text-[#FF6B16]"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        class="inline-flex rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                        :class="
                                            item.is_active
                                                ? 'border-red-200 text-red-600 hover:bg-red-50'
                                                : 'border-green-200 text-green-700 hover:bg-green-50'
                                            "
                                        @click="changeStatus(item)"
                                    >
                                        {{ item.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="produk.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="text-3xl text-slate-300">⌕</div>
                                    <p class="mt-3 font-semibold text-slate-700">
                                        Produk tidak ditemukan
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Coba ubah kata kunci atau filter pencarian.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-6"
            >
                <p class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-semibold text-slate-700">
                        {{ showingFrom }}–{{ showingTo }}
                    </span>
                    dari
                    <span class="font-semibold text-slate-700">
                        {{ produk.total }}
                    </span>
                    produk
                </p>

                <div class="flex flex-wrap items-center gap-1">
                    <button
                        v-for="link in produk.links"
                        :key="link.label"
                        type="button"
                        :disabled="!link.url"
                        class="min-w-9 rounded-lg border px-3 py-2 text-sm transition disabled:cursor-not-allowed disabled:opacity-40"
                        :class="
                            link.active
                                ? 'border-[#08264F] bg-[#08264F] font-semibold text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                        "
                        @click="goToPage(link.url)"
                    >
                        {{ paginationLabel(link.label) }}
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
