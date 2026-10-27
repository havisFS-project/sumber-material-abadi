
<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type KategoriItem = {
    id: number;
    nama: string;
    slug: string;
    produk_count: number;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    kategori: {
        data: KategoriItem[];
        links: PaginationLink[];
        current_page: number;
        total: number;
    };
    filters: {
        q: string;
    };
}>();

const search = ref(props.filters.q ?? '');

function applyFilters() {
    router.get(
        '/admin/kategori',
        {
            q: search.value.trim() || undefined,
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

    router.get(
        '/admin/kategori',
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

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Manajemen Kategori" />

    <div class="space-y-6">
        <section class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    INVENTARIS TOKO
                </p>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#08264F] sm:text-3xl">
                    Manajemen Kategori
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Kelompokkan produk agar katalog toko lebih teratur.
                </p>
            </div>

            <Link
                href="/admin/kategori/create"
                class="inline-flex items-center justify-center rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
            >
                + Tambah Kategori
            </Link>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Total Kategori
                    </p>
                    <span class="rounded-xl bg-blue-50 px-3 py-2 text-lg text-[#08264F]">
                        ▤
                    </span>
                </div>

                <p class="mt-4 text-3xl font-bold text-[#08264F]">
                    {{ kategori.total }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Seluruh kategori terdaftar
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Produk Terhubung
                    </p>
                    <span class="rounded-xl bg-green-50 px-3 py-2 text-lg text-green-700">
                        ✓
                    </span>
                </div>

                <p class="mt-4 text-3xl font-bold text-green-700">
                    {{ kategori.data.reduce((total, item) => total + item.produk_count, 0) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Produk pada halaman yang sedang ditampilkan
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Daftar Kategori
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Cari dan kelola nama kategori toko.
                        </p>
                    </div>

                    <span class="text-sm text-slate-500">
                        {{ kategori.total }} kategori
                    </span>
                </div>

                <form
                    class="mt-5 flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="applyFilters"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nama atau slug kategori..."
                        aria-label="Cari kategori"
                        class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                    />

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
                <table class="w-full min-w-[650px] border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 font-semibold">Kategori</th>
                            <th class="px-5 py-4 font-semibold">Slug</th>
                            <th class="px-5 py-4 font-semibold">Jumlah Produk</th>
                            <th class="px-6 py-4 font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="item in kategori.data"
                            :key="item.id"
                            class="transition hover:bg-slate-50/70"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-[#08264F]">
                                        {{ item.nama.charAt(0).toUpperCase() }}
                                    </div>

                                    <span class="font-semibold text-slate-800">
                                        {{ item.nama }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <code class="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600">
                                    {{ item.slug }}
                                </code>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-[#08264F]">
                                    {{ item.produk_count }} produk
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <Link
                                    :href="`/admin/kategori/${item.id}/edit`"
                                    class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#08264F] transition hover:border-[#FF6B16] hover:text-[#FF6B16]"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="kategori.data.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center">
                                <p class="font-semibold text-slate-700">
                                    Kategori tidak ditemukan
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Coba kata kunci lain atau tambahkan kategori baru.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                <p class="text-sm text-slate-500">
                    Halaman {{ kategori.current_page }} dari
                    {{ Math.max(1, Math.ceil(kategori.total / 10)) }}
                </p>

                <div class="flex flex-wrap items-center gap-1">
                    <button
                        v-for="link in kategori.links"
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
