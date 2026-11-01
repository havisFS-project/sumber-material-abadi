
<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

type Kategori = {
    id: number;
    nama: string;
};

type Produk = {
    id: number;
    kode: string;
    nama: string;
    satuan: string;
    deskripsi: string | null;
    kategori: string | null;
    harga_jual: number;
    harga_akhir: number;
    potongan: number;
    diskon_aktif: boolean;
    stok: number;
    punya_garansi: boolean;
    masa_garansi_bulan: number | null;
    foto_url: string | null;
};

type PromoProduk = {
    nama: string;
    harga_akhir: number;
    diskon_persen: number;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type StoreInfo = {
    nama: string;
    alamat: string;
    maps_url: string;
    whatsapp: string;
    weekday_hours: string;
    sunday_hours: string;
};

const props = defineProps<{
    produk: {
        data: Produk[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    kategoris: Kategori[];
    promoProduk: PromoProduk[];
    filters: {
        q: string;
        kategori: string;
    };
    totalProduk: number;
    store: StoreInfo;
}>();

const search = ref(props.filters.q ?? '');
const kategori = ref(props.filters.kategori ?? '');

// Format mata uang Rupiah.
function rupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

// Menghitung persentase diskon berdasarkan harga.
function persentaseDiskon(item: Produk): number {
    if (item.harga_jual <= 0 || item.potongan <= 0) {
        return 0;
    }

    return Math.round(
        (item.potongan / item.harga_jual) * 100,
    );
}

// Membuat URL WhatsApp umum toko.
function whatsappTokoUrl(): string | null {
    const phone = (props.store.whatsapp ?? '').replace(/\D/g, '');

    if (!phone) return null;

    const message =
        'Halo TB. Sumber Material Abadi, saya ingin bertanya mengenai produk dan layanan toko.';

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

// Membuat URL WhatsApp berdasarkan produk.
function whatsappProdukUrl(item: Produk): string | null {
    const phone = (props.store.whatsapp ?? '').replace(/\D/g, '');

    if (!phone) return null;

    const message = [
        'Halo TB. Sumber Material Abadi.',
        '',
        'Saya tertarik dengan produk berikut:',
        `Nama produk: ${item.nama}`,
        `Kode produk: ${item.kode}`,
        `Harga: ${rupiah(item.harga_akhir)} per ${item.satuan}`,
        `Ketersediaan: ${item.stok > 0 ? 'Masih tersedia' : 'Ingin menanyakan ketersediaan stok'}`,
        '',
        'Apakah produk tersebut masih tersedia? Terima kasih.',
    ].join('\n');

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

// Menjalankan pencarian.
function applyFilters() {
    router.get(
        '/katalog',
        {
            q: search.value.trim() || undefined,
            kategori: kategori.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

// Filter kategori melalui tombol.
function filterByCategory(id: string) {
    kategori.value = id;
    applyFilters();
}

// Reset pencarian dan filter.
function resetFilters() {
    search.value = '';
    kategori.value = '';

    router.get('/katalog', {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Navigasi pagination.
function goToPage(url: string | null) {
    if (!url) return;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Merapikan label pagination Laravel.
function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Katalog Produk - Sumber Material Abadi" />

    <div class="min-h-screen bg-[#F4F7FB] text-slate-800">

        <!-- HEADER NAVIGASI -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

                <!-- BRAND -->
                <a href="/" class="flex shrink-0 items-center gap-2.5">
                    <svg
                        viewBox="0 0 64 64"
                        class="h-11 w-11"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path d="M7 24 32 7 57 24 32 37 7 24Z" fill="#FF6B16"/>
                        <path d="M7 24 32 37 57 24" stroke="#08264F" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M15 27V49H27V35L32 38L37 35V49H49V27" stroke="#08264F" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M5 52H59" stroke="#08264F" stroke-width="4" stroke-linecap="round"/>
                        <path d="M23 18 32 12 41 18" stroke="#08264F" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>

                    <div class="leading-tight">
                        <h1 class="text-sm font-extrabold text-[#08264F] sm:text-base">
                            TB. Sumber
                            <br />
                            Material Abadi
                        </h1>
                    </div>
                </a>

                <!-- NAVIGASI -->
                <nav class="hidden items-center gap-6 md:flex">
                    <a
                        href="#katalog"
                        class="text-sm font-semibold text-[#FF6B16]"
                    >
                        Katalog
                    </a>

                    <Link
                        href="/garansi"
                        class="text-sm font-medium text-slate-600 transition hover:text-[#FF6B16]"
                    >
                        Cek Garansi
                    </Link>

                    <a
                        href="#kontak"
                        class="text-sm font-medium text-slate-600 transition hover:text-[#FF6B16]"
                    >
                        Kontak
                    </a>
                </nav>

                <!-- KONTAK HEADER -->
                <div class="flex shrink-0 items-center gap-2">
                    <a
                        v-if="store.maps_url"
                        :href="store.maps_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-[#08264F] transition hover:border-[#08264F] hover:bg-slate-50 sm:inline-flex"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                        Alamat
                    </a>

                    <a
                        v-if="store.whatsapp"
                        :href="whatsappTokoUrl() ?? '#kontak'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700 sm:px-4"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-12.5 7.3L3 20l1.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9c.3 1.8 2.2 3.7 4 4l1.2-.9 1.8.9"/>
                        </svg>
                        <span class="hidden sm:inline">Chat WhatsApp</span>
                        <span class="sm:hidden">WhatsApp</span>
                    </a>
                </div>
            </div>

            <!-- NAVIGASI MOBILE -->
            <div class="flex items-center justify-center gap-6 border-t border-slate-100 px-4 py-2 md:hidden">
                <a href="#katalog" class="text-xs font-semibold text-[#FF6B16]">
                    Katalog
                </a>

                <Link href="/garansi" class="text-xs font-medium text-slate-600">
                    Cek Garansi
                </Link>

                <a href="#kontak" class="text-xs font-medium text-slate-600">
                    Kontak
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

            <!-- HERO SECTION -->
            <section class="relative overflow-hidden rounded-3xl bg-[#06234B] px-6 py-9 text-white shadow-lg sm:px-10 sm:py-11">

                <!-- DEKORASI -->
                <div class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 rounded-full bg-[#FF6B16]/15"></div>

                <div class="pointer-events-none absolute -bottom-36 right-[42%] h-72 w-72 rounded-full bg-blue-300/10"></div>

                <div class="relative z-10 grid grid-cols-1 items-center gap-8 lg:grid-cols-[1.3fr_0.8fr]">

                    <!-- HERO COPY -->
                    <div>
                        <span class="inline-flex rounded-full bg-[#FF6B16] px-3 py-1 text-[11px] font-bold text-white">
                            Material & Konstruksi · Terpercaya
                        </span>

                        <h2 class="mt-5 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl lg:text-5xl">
                            Bahan bangunan lengkap,
                            <span class="text-[#FF762C]">
                                harga jelas.
                            </span>
                        </h2>

                        <p class="mt-4 max-w-xl text-sm leading-6 text-blue-100 sm:text-base">
                            Cek produk, promo, dan garansi dari rumah.
                            Transaksi dilakukan langsung di kasir toko.
                        </p>

                        <!-- PENCARIAN -->
                        <form
                            class="mt-6 flex max-w-xl items-center gap-2 rounded-2xl bg-white p-2 shadow-lg"
                            @submit.prevent="applyFilters"
                        >
                            <svg
                                class="ml-2 h-5 w-5 shrink-0 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="7"/>
                                <path stroke-linecap="round" d="m20 20-4-4"/>
                            </svg>

                            <input
                                v-model="search"
                                type="search"
                                placeholder="Cari semen, cat, pipa, toren..."
                                class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2.5 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                            />

                            <button
                                type="submit"
                                class="rounded-xl bg-[#FF6B16] px-5 py-3 text-xs font-bold text-white transition hover:bg-orange-600"
                            >
                                Cari
                            </button>
                        </form>
                    </div>

                    <!-- PROMO MINGGU INI -->
                    <div class="relative rounded-2xl border border-white/10 bg-white/15 p-5 shadow-lg backdrop-blur-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold">
                                Promo Produk
                            </h3>

                            <span class="rounded-full bg-[#FF6B16] px-2.5 py-1 text-[10px] font-bold">
                                SPECIAL PRICE
                            </span>
                        </div>

                        <div v-if="promoProduk.length > 0" class="mt-4 space-y-3">
                            <div
                                v-for="promo in promoProduk"
                                :key="promo.nama"
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-xs text-blue-50">
                                        {{ promo.nama }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-white">
                                        {{ rupiah(promo.harga_akhir) }}
                                    </p>
                                </div>

                                <span
                                    v-if="promo.diskon_persen > 0"
                                    class="shrink-0 rounded-full bg-[#FF6B16] px-2.5 py-1 text-[10px] font-bold"
                                >
                                    -{{ promo.diskon_persen }}%
                                </span>
                            </div>
                        </div>

                        <p v-else class="mt-4 text-xs leading-6 text-blue-100">
                            Nantikan promo menarik dari Sumber Material Abadi.
                        </p>

                        <div class="mt-5 border-t border-white/15 pt-3">
                            <p class="text-[11px] text-blue-100">
                                Harga dan ketersediaan dapat dikonfirmasi
                                langsung melalui WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KATALOG -->
            <section id="katalog" class="mt-8 scroll-mt-28">

                <!-- KATEGORI -->
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-full border px-4 py-2 text-xs font-semibold transition"
                        :class="kategori === ''
                            ? 'border-[#08264F] bg-[#08264F] text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-[#08264F]'"
                        @click="filterByCategory('')"
                    >
                        Semua
                    </button>

                    <button
                        v-for="item in kategoris"
                        :key="item.id"
                        type="button"
                        class="rounded-full border px-4 py-2 text-xs font-semibold transition"
                        :class="kategori === String(item.id)
                            ? 'border-[#08264F] bg-[#08264F] text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-[#08264F]'"
                        @click="filterByCategory(String(item.id))"
                    >
                        {{ item.nama }}
                    </button>
                </div>

                <!-- JUDUL KATALOG -->
                <div class="mt-7 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#FF6B16]">
                            PILIHAN MATERIAL
                        </p>

                        <h3 class="mt-1 text-2xl font-extrabold text-[#08264F]">
                            Semua Produk
                        </h3>
                    </div>

                    <p class="text-xs text-slate-500">
                        Menampilkan {{ produk.data.length }} dari
                        {{ produk.total }} produk
                    </p>
                </div>

                <!-- GRID PRODUK -->
                <div
                    v-if="produk.data.length > 0"
                    class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <article
                        v-for="item in produk.data"
                        :key="item.id"
                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                    >
                        <!-- AREA FOTO -->
                        <div class="relative flex aspect-[5/4] items-center justify-center overflow-hidden bg-[#E9EFF8] p-4">

                            <img
                                v-if="item.foto_url"
                                :src="item.foto_url"
                                :alt="item.nama"
                                loading="lazy"
                                class="h-full w-full object-contain transition duration-500 group-hover:scale-105"
                            />

                            <div
                                v-else
                                class="flex flex-col items-center gap-2 text-[#8FA2BC]"
                            >
                                <svg
                                    class="h-12 w-12 sm:h-14 sm:w-14"
                                    viewBox="0 0 64 64"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    aria-hidden="true"
                                >
                                    <path d="M32 5 56 18V46L32 59 8 46V18L32 5Z"/>
                                    <path d="M8 18 32 32 56 18"/>
                                    <path d="M32 32V59"/>
                                    <path d="m20 11 24 13"/>
                                </svg>

                                <span class="text-[10px]">
                                    Foto belum tersedia
                                </span>
                            </div>

                            <!-- BADGE DISKON -->
                            <span
                                v-if="item.potongan > 0"
                                class="absolute left-2.5 top-2.5 rounded-full bg-[#FF6B16] px-2.5 py-1 text-[10px] font-extrabold text-white shadow-sm"
                            >
                                -{{ persentaseDiskon(item) }}%
                            </span>

                            <!-- BADGE GARANSI -->
                            <span
                                v-if="item.punya_garansi"
                                class="absolute right-2.5 top-2.5 rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-bold text-blue-700"
                            >
                                Bergaransi
                            </span>

                            <!-- BADGE STOK HABIS -->
                            <span
                                v-if="item.stok <= 0"
                                class="absolute bottom-2.5 left-2.5 rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-bold text-slate-600"
                            >
                                Stok habis
                            </span>
                        </div>

                        <!-- DETAIL PRODUK -->
                        <div class="p-3.5 sm:p-4">
                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">
                                {{ item.kategori ?? 'Umum' }}
                            </p>

                            <h4
                                class="mt-1 line-clamp-2 min-h-10 text-xs font-bold leading-5 text-[#08264F] sm:text-sm"
                                :title="item.nama"
                            >
                                {{ item.nama }}
                            </h4>

                            <p class="mt-1 truncate text-[10px] text-slate-400">
                                {{ item.satuan }} · {{ item.kode }}
                            </p>

                            <!-- HARGA -->
                            <div class="mt-3 min-h-12">
                                <p
                                    v-if="item.potongan > 0"
                                    class="text-[10px] text-slate-400 line-through sm:text-xs"
                                >
                                    {{ rupiah(item.harga_jual) }}
                                </p>

                                <p class="text-sm font-extrabold text-[#08264F] sm:text-base">
                                    {{ rupiah(item.harga_akhir) }}
                                </p>
                            </div>

                            <!-- KONTAK PRODUK -->
                            <a
                                v-if="store.whatsapp"
                                :href="whatsappProdukUrl(item) ?? '#kontak'"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl bg-green-100 px-2 py-2.5 text-[10px] font-bold text-green-700 transition hover:bg-green-600 hover:text-white sm:text-xs"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-12.5 7.3L3 20l1.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9c.3 1.8 2.2 3.7 4 4l1.2-.9 1.8.9"/>
                                </svg>

                                {{ item.stok > 0 ? 'Tanya via WhatsApp' : 'Tanya Ketersediaan' }}
                            </a>

                            <p
                                v-else
                                class="mt-3 rounded-xl bg-slate-100 px-2 py-2.5 text-center text-[10px] font-medium text-slate-500 sm:text-xs"
                            >
                                Hubungi toko untuk informasi
                            </p>
                        </div>
                    </article>
                </div>

                <!-- JIKA PRODUK TIDAK DITEMUKAN -->
                <div
                    v-else
                    class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-slate-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" d="m20 20-4-4"/>
                    </svg>

                    <h4 class="mt-4 font-bold text-[#08264F]">
                        Produk tidak ditemukan
                    </h4>

                    <p class="mt-2 text-sm text-slate-500">
                        Coba gunakan kata kunci atau kategori lainnya.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-xl bg-[#08264F] px-5 py-3 text-sm font-semibold text-white hover:bg-[#10386C]"
                        @click="resetFilters"
                    >
                        Lihat Semua Produk
                    </button>
                </div>

                <!-- PAGINATION -->
                <div
                    v-if="produk.last_page > 1"
                    class="mt-8 flex flex-col justify-between gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center"
                >
                    <p class="text-xs text-slate-500">
                        Halaman {{ produk.current_page }}
                        dari {{ produk.last_page }}
                    </p>

                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="link in produk.links"
                            :key="link.label"
                            type="button"
                            :disabled="!link.url"
                            class="min-w-9 rounded-lg border px-3 py-2 text-xs transition disabled:cursor-not-allowed disabled:opacity-40"
                            :class="link.active
                                ? 'border-[#08264F] bg-[#08264F] font-semibold text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                            @click="goToPage(link.url)"
                        >
                            {{ paginationLabel(link.label) }}
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER KONTAK -->
        <footer id="kontak" class="scroll-mt-20 bg-[#06234B] text-white">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-9 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">

                <!-- BRAND FOOTER -->
                <div>
                    <div class="inline-flex items-center gap-3 rounded-xl bg-white px-3 py-2.5 text-[#08264F]">
                        <svg
                            viewBox="0 0 64 64"
                            class="h-10 w-10"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <path d="M7 24 32 7 57 24 32 37 7 24Z" fill="#FF6B16"/>
                            <path d="M7 24 32 37 57 24" stroke="#08264F" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M15 27V49H27V35L32 38L37 35V49H49V27" stroke="#08264F" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M5 52H59" stroke="#08264F" stroke-width="4" stroke-linecap="round"/>
                        </svg>

                        <span class="text-xs font-extrabold leading-tight">
                            TB. Sumber
                            <br/>
                            Material Abadi
                        </span>
                    </div>

                    <p class="mt-4 max-w-xs text-xs leading-6 text-blue-100">
                        Semen, besi, cat, pipa, keramik, perkakas,
                        dan berbagai kebutuhan material bangunan
                        dalam satu toko.
                    </p>
                </div>

                <!-- ALAMAT DAN JAM OPERASIONAL -->
                <div>
                    <h3 class="text-sm font-bold">
                        Alamat & Jam Buka
                    </h3>

                    <div class="mt-4 flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-orange-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>

                        <p class="text-xs leading-6 text-blue-100">
                            {{ store.alamat || 'Alamat toko belum dikonfigurasi.' }}
                        </p>
                    </div>

                    <div class="mt-3 flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-orange-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                        </svg>

                        <div class="text-xs leading-6 text-blue-100">
                            <p>
                                {{ store.weekday_hours || 'Jam operasional belum dikonfigurasi.' }}
                            </p>

                            <p>
                                {{ store.sunday_hours || 'Informasi hari Minggu belum tersedia.' }}
                            </p>
                        </div>
                    </div>

                    <a
                        v-if="store.maps_url"
                        :href="store.maps_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#08264F] transition hover:bg-blue-50"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 18a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H9Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10 10 4 4m0-4-4 4"/>
                        </svg>

                        Buka di Maps
                    </a>
                </div>

                <!-- KONTAK -->
                <div>
                    <h3 class="text-sm font-bold">
                        Hubungi Kami
                    </h3>

                    <p class="mt-3 text-xs leading-6 text-blue-100">
                        Ada pertanyaan tentang produk, harga, atau stok?
                        Hubungi tim toko kami.
                    </p>

                    <a
                        v-if="store.whatsapp"
                        :href="whatsappTokoUrl() ?? '#kontak'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-3 text-xs font-bold text-white transition hover:bg-green-700"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-12.5 7.3L3 20l1.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9c.3 1.8 2.2 3.7 4 4l1.2-.9 1.8.9"/>
                        </svg>

                        Chat WhatsApp
                    </a>

                    <p v-else class="mt-4 text-xs text-blue-200">
                        Kontak WhatsApp toko belum tersedia.
                    </p>

                    <Link
                        href="/garansi"
                        class="mt-3 inline-flex items-center gap-2 text-xs font-medium text-blue-100 transition hover:text-white"
                    >
                        Periksa Garansi Produk
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>
            </div>

            <!-- COPYRIGHT -->
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col justify-between gap-2 px-4 py-5 text-center sm:flex-row sm:px-6 sm:text-left lg:px-8">
                    <p class="text-[11px] text-blue-200">
                        © {{ new Date().getFullYear() }} TB. Sumber Material Abadi.
                    </p>

                    <p class="text-[11px] text-blue-200">
                        Katalog Produk Digital
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
