
<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

type ProdukItem = {
    id: number;
    kode: string;
    nama: string;
    kategori: string | null;
    satuan: string;
    harga_jual: number;
    harga_akhir: number;
    potongan: number;
    diskon_aktif: boolean;
    stok: number;
    punya_garansi: boolean;
    masa_garansi_bulan: number | null;
};

type CartItem = ProdukItem & {
    jumlah: number;
};

const props = defineProps<{
    produk: ProdukItem[];
    filters: {
        q: string;
    };
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}>();

const search = ref(props.filters.q ?? '');
const keranjang = ref<CartItem[]>([]);

const checkoutForm = useForm({
    items: [] as Array<{
        id: number;
        jumlah: number;
    }>,
    metode_bayar: 'tunai' as 'tunai' | 'qris',
    uang_diterima: 0 as number | null,
    nama_pelanggan: '',
    no_hp_pelanggan: '',
});

const dismissedSuccess = ref(false);

watch(
    () => props.flash?.success,
    () => {
        dismissedSuccess.value = false;
    },
);

const subtotal = computed(() =>
    keranjang.value.reduce(
        (total, item) => total + item.harga_jual * item.jumlah,
        0,
    ),
);

const totalDiskon = computed(() =>
    keranjang.value.reduce(
        (total, item) => total + item.potongan * item.jumlah,
        0,
    ),
);

const totalBayar = computed(() =>
    keranjang.value.reduce(
        (total, item) => total + item.harga_akhir * item.jumlah,
        0,
    ),
);

const kembalian = computed(() =>
    Math.max(
        0,
        Number(checkoutForm.uang_diterima ?? 0) - totalBayar.value,
    ),
);

const uangKurang = computed(
    () =>
        checkoutForm.metode_bayar === 'tunai' &&
        Number(checkoutForm.uang_diterima ?? 0) < totalBayar.value,
);

const totalItem = computed(() =>
    keranjang.value.reduce(
        (total, item) => total + item.jumlah,
        0,
    ),
);

function rupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function cariProduk() {
    router.get(
        '/kasir',
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

function jumlahDiKeranjang(produkId: number): number {
    return keranjang.value.find(
        (item) => item.id === produkId,
    )?.jumlah ?? 0;
}

function tambahProduk(produk: ProdukItem) {
    if (produk.stok < 1) {
        return;
    }

    const item = keranjang.value.find(
        (cartItem) => cartItem.id === produk.id,
    );

    if (item) {
        if (item.jumlah >= produk.stok) {
            window.alert('Jumlah melebihi stok yang tersedia.');
            return;
        }

        item.jumlah++;
        return;
    }

    keranjang.value.push({
        ...produk,
        jumlah: 1,
    });
}

function kurangiJumlah(produkId: number) {
    const item = keranjang.value.find(
        (cartItem) => cartItem.id === produkId,
    );

    if (item && item.jumlah > 1) {
        item.jumlah--;
    }
}

function hapusProduk(produkId: number) {
    keranjang.value = keranjang.value.filter(
        (item) => item.id !== produkId,
    );
}

function kosongkanKeranjang() {
    if (keranjang.value.length === 0) {
        return;
    }

    if (window.confirm('Kosongkan semua barang di keranjang?')) {
        keranjang.value = [];
    }
}



function prosesPembayaran() {
    if (keranjang.value.length === 0) {
        return;
    }

    // Sembunyikan pesan sebelumnya saat checkout baru dimulai.
    dismissedSuccess.value = true;
    checkoutForm.clearErrors();

    // Validasi awal di frontend. Backend tetap memvalidasi ulang.
    if (
        checkoutForm.metode_bayar === 'tunai' &&
        Number(checkoutForm.uang_diterima ?? 0) < totalBayar.value
    ) {
        checkoutForm.setError(
            'uang_diterima',
            'Uang diterima kurang dari total pembayaran.',
        );

        return;
    }

    // Kirim hanya ID dan jumlah barang, bukan harga dari browser.
    checkoutForm.items = keranjang.value.map((item) => ({
        id: item.id,
        jumlah: item.jumlah,
    }));

    // QRIS masih berupa simulasi.
    if (checkoutForm.metode_bayar === 'qris') {
        checkoutForm.uang_diterima = null;
    }

    checkoutForm.post('/kasir/checkout', {
        preserveScroll: true,

        onSuccess: () => {
            // Kosongkan keranjang setelah server mengonfirmasi sukses.
            keranjang.value = [];

            checkoutForm.reset();
            checkoutForm.clearErrors();

            // Pesan invoice dibaca langsung dari props.flash
            // yang diterima setelah redirect dari Laravel.
        },
    });
}

</script>

<template>
    <Head title="POS Kasir" />

    <div class="min-h-screen bg-[#F4F7FB] text-slate-800">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-[#08264F] text-white shadow-sm">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#FF6B16] font-black">
                        SMA
                    </div>

                    <div>
                        <h1 class="font-bold tracking-wide">
                            Sumber Material Abadi
                        </h1>
                        <p class="text-xs text-blue-200">
                            Point of Sale
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    <span class="hidden text-sm text-blue-100 sm:inline">
                        Mode Kasir
                    </span>

                    <Link
                        href="/kasir/riwayat"
                        class="rounded-lg border border-white/20 px-3 py-2 text-sm font-semibold transition hover:bg-white/10"
                    >
                        Riwayat
                    </Link>

                    <Link
                        href="/dashboard"
                        class="rounded-lg border border-white/20 px-3 py-2 text-sm font-medium transition hover:bg-white/10"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] p-4 sm:p-6 lg:p-8">
            <section class="mb-6">
                <p class="text-sm font-medium text-[#FF6B16]">
                    PENJUALAN TOKO
                </p>

                <h2 class="mt-1 text-2xl font-bold text-[#08264F] sm:text-3xl">
                    Kasir POS
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Cari barang, masukkan ke keranjang, dan periksa total belanja.
                </p>
            </section>

            <div
                v-if="props.flash?.success && !dismissedSuccess"
                role="status"
                class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800"
            >
                <span>{{ props.flash.success }}</span>

                <button
                    type="button"
                    class="rounded-lg px-2 text-lg leading-none hover:bg-green-100"
                    aria-label="Tutup notifikasi"
                    @click="dismissedSuccess = true"
                >
                    ×
                </button>
            </div>

            <div
                v-if="props.flash?.error"
                role="alert"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800"
            >
                {{ props.flash.error }}
            </div>

            <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(360px,1fr)]">
                <section class="min-w-0 space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <form
                            class="flex flex-col gap-3 sm:flex-row"
                            @submit.prevent="cariProduk"
                        >
                            <input
                                v-model="search"
                                type="search"
                                aria-label="Cari produk"
                                placeholder="Cari nama atau kode produk..."
                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                            />

                            <button
                                type="submit"
                                class="rounded-xl bg-[#08264F] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10386C]"
                            >
                                Cari Produk
                            </button>
                        </form>

                        <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
                            <span>Hasil pencarian</span>
                            <span>{{ produk.length }} produk</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <article
                            v-for="item in produk"
                            :key="item.id"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-[#08264F]">
                                    {{ item.nama.charAt(0).toUpperCase() }}
                                </div>

                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        item.stok > 5
                                            ? 'bg-green-50 text-green-700'
                                            : item.stok > 0
                                              ? 'bg-orange-50 text-orange-700'
                                              : 'bg-red-50 text-red-600'
                                    "
                                >
                                    {{
                                        item.stok > 0
                                            ? `Stok ${item.stok}`
                                            : 'Stok habis'
                                    }}
                                </span>
                            </div>

                            <p class="mt-4 text-xs font-medium text-slate-400">
                                {{ item.kode }}
                                <span v-if="item.kategori">
                                    · {{ item.kategori }}
                                </span>
                            </p>

                            <h3 class="mt-1 min-h-12 font-bold leading-6 text-[#08264F]">
                                {{ item.nama }}
                            </h3>

                            <p class="mt-3 text-xs text-slate-500">
                                Harga per {{ item.satuan }}
                            </p>

                            <p class="mt-1 text-lg font-bold text-[#08264F]">
                                {{ rupiah(item.harga_akhir) }}
                            </p>

                            <p
                                v-if="item.diskon_aktif && item.potongan > 0"
                                class="mt-1 text-xs text-slate-400"
                            >
                                <span class="line-through">
                                    {{ rupiah(item.harga_jual) }}
                                </span>
                                <span class="ml-2 font-semibold text-[#FF6B16]">
                                    Hemat {{ rupiah(item.potongan) }}
                                </span>
                            </p>

                            <button
                                type="button"
                                :disabled="
                                    item.stok < 1 ||
                                    jumlahDiKeranjang(item.id) >= item.stok
                                "
                                class="mt-4 w-full rounded-xl bg-[#FF6B16] px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                                @click="tambahProduk(item)"
                            >
                                {{
                                    item.stok < 1
                                        ? 'Stok Habis'
                                        : jumlahDiKeranjang(item.id) >= item.stok
                                          ? 'Stok Maksimal di Keranjang'
                                          : '+ Tambah ke Keranjang'
                                }}
                            </button>
                        </article>

                        <div
                            v-if="produk.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center md:col-span-2"
                        >
                            <div class="text-3xl text-slate-300">⌕</div>
                            <p class="mt-3 font-semibold text-slate-700">
                                Produk tidak ditemukan
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Coba nama atau kode produk yang berbeda.
                            </p>
                        </div>
                    </div>
                </section>

                <aside class="min-w-0 rounded-2xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-24">
                    <div class="flex items-center justify-between border-b border-slate-100 p-5">
                        <div>
                            <h3 class="text-lg font-bold text-[#08264F]">
                                Keranjang Belanja
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ totalItem }} barang
                            </p>
                        </div>

                        <button
                            type="button"
                            class="text-sm font-semibold text-red-600 transition hover:text-red-700 disabled:opacity-40"
                            :disabled="keranjang.length === 0"
                            @click="kosongkanKeranjang"
                        >
                            Kosongkan
                        </button>
                    </div>

                    <div class="max-h-[440px] space-y-4 overflow-y-auto p-5">
                        <div
                            v-if="keranjang.length === 0"
                            class="py-12 text-center"
                        >
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">
                                <span class="text-slate-400">▤</span>
                            </div>

                            <p class="mt-4 font-semibold text-slate-700">
                                Keranjang masih kosong
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Tambahkan produk untuk memulai transaksi.
                            </p>
                        </div>

                        <article
                            v-for="item in keranjang"
                            :key="item.id"
                            class="border-b border-slate-100 pb-4 last:border-b-0"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h4 class="font-semibold leading-5 text-slate-800">
                                        {{ item.nama }}
                                    </h4>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ rupiah(item.harga_akhir) }} / {{ item.satuan }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                                    @click="hapusProduk(item.id)"
                                >
                                    Hapus
                                </button>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                                        aria-label="Kurangi jumlah"
                                        :disabled="item.jumlah <= 1"
                                        @click="kurangiJumlah(item.id)"
                                    >
                                        −
                                    </button>

                                    <span class="min-w-6 text-center text-sm font-semibold">
                                        {{ item.jumlah }}
                                    </span>

                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                                        aria-label="Tambah jumlah"
                                        :disabled="item.jumlah >= item.stok"
                                        @click="tambahProduk(item)"
                                    >
                                        +
                                    </button>
                                </div>

                                <p class="text-sm font-bold text-[#08264F]">
                                    {{ rupiah(item.harga_akhir * item.jumlah) }}
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="space-y-3 border-t border-slate-100 bg-slate-50/70 p-5">
                        
                        <div class="space-y-4 border-b border-slate-200 pb-5">
                            <h4 class="font-bold text-[#08264F]">
                                Informasi Pelanggan
                            </h4>

                            <div>
                                <label
                                    for="nama_pelanggan"
                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Nama pelanggan (opsional)
                                </label>

                                <input
                                    id="nama_pelanggan"
                                    v-model="checkoutForm.nama_pelanggan"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Nama pelanggan"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                />

                                <p
                                    v-if="checkoutForm.errors.nama_pelanggan"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ checkoutForm.errors.nama_pelanggan }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="no_hp_pelanggan"
                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Nomor HP (opsional)
                                </label>

                                <input
                                    id="no_hp_pelanggan"
                                    v-model="checkoutForm.no_hp_pelanggan"
                                    type="tel"
                                    maxlength="30"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                />

                                <p
                                    v-if="checkoutForm.errors.no_hp_pelanggan"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ checkoutForm.errors.no_hp_pelanggan }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 border-b border-slate-200 pb-5">
                            <label
                                for="metode_bayar"
                                class="block text-sm font-bold text-[#08264F]"
                            >
                                Metode Pembayaran
                            </label>

                            <select
                                id="metode_bayar"
                                v-model="checkoutForm.metode_bayar"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            >
                                <option value="tunai">Tunai</option>
                                <option value="qris">QRIS (Simulasi)</option>
                            </select>

                            <p
                                v-if="checkoutForm.errors.metode_bayar"
                                class="text-xs text-red-600"
                            >
                                {{ checkoutForm.errors.metode_bayar }}
                            </p>

                            <template v-if="checkoutForm.metode_bayar === 'tunai'">
                                <label
                                    for="uang_diterima"
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Uang Diterima (Rp)
                                </label>

                                <input
                                    id="uang_diterima"
                                    v-model.number="checkoutForm.uang_diterima"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="Masukkan nominal uang"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                    @input="checkoutForm.clearErrors('uang_diterima')"
                                />

                                <p
                                    v-if="checkoutForm.errors.uang_diterima"
                                    class="text-xs text-red-600"
                                >
                                    {{ checkoutForm.errors.uang_diterima }}
                                </p>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Perkiraan kembalian</span>

                                    <span
                                        class="font-semibold"
                                        :class="uangKurang ? 'text-red-600' : 'text-green-700'"
                                    >
                                        {{ rupiah(kembalian) }}
                                    </span>
                                </div>

                                <p
                                    v-if="uangKurang && totalBayar > 0"
                                    class="text-xs text-red-600"
                                >
                                    Uang yang diterima belum mencukupi.
                                </p>
                            </template>

                            <p
                                v-else
                                class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs leading-5 text-orange-800"
                            >
                                QRIS hanya simulasi. Belum ada verifikasi pembayaran dari
                                penyedia QRIS nyata.
                            </p>
                        </div>

                        <p
                            v-if="checkoutForm.errors.items"
                            role="alert"
                            class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                        >
                            {{ checkoutForm.errors.items }}
                        </p>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Subtotal harga awal</span>
                            <span class="font-medium text-slate-700">
                                {{ rupiah(subtotal) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Total diskon</span>
                            <span class="font-medium text-green-700">
                                − {{ rupiah(totalDiskon) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                            <span class="font-semibold text-[#08264F]">
                                Total Bayar
                            </span>
                            <span class="text-xl font-bold text-[#08264F]">
                                {{ rupiah(totalBayar) }}
                            </span>
                        </div>

                        <button
                            type="button"
                            :disabled="
                                keranjang.length === 0 ||
                                checkoutForm.processing
                            "
                            class="w-full rounded-xl bg-[#FF6B16] px-4 py-3.5 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                            @click="prosesPembayaran"
                        >
                            {{
                                checkoutForm.processing
                                    ? 'Memproses Transaksi...'
                                    : checkoutForm.metode_bayar === 'qris'
                                    ? 'Konfirmasi QRIS (Simulasi)'
                                    : 'Proses Pembayaran'
                            }}
                        </button>

                        <p class="mt-2 text-center text-xs text-slate-400">
                            {{
                                checkoutForm.processing
                                    ? 'Mohon tunggu, transaksi sedang diproses.'
                                    : 'Harga, stok, dan total akan divalidasi oleh server.'
                            }}
                        </p>

                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>
