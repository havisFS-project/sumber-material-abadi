<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

type HasilGaransi = {
    no_garansi: string;
    nama_produk: string;
    tanggal_mulai: string;
    tanggal_berakhir: string;
    masa_berlaku: 'aktif' | 'berakhir';
    status_klaim: string;
};

const props = defineProps<{
    dicari: boolean;
    noGaransi: string;
    hasil: HasilGaransi | null;
}>();

const nomor = ref(props.noGaransi);

function cariGaransi() {
    const query = nomor.value.trim();

    if (!query) return;

    router.get(
        '/garansi',
        { no_garansi: query },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function statusKlaim(status: string): string {
    const labels: Record<string, string> = {
        belum_ada: 'Belum ada klaim',
        diajukan: 'Klaim diajukan',
        diverifikasi: 'Sedang diverifikasi',
        disetujui: 'Klaim disetujui',
        ditolak: 'Klaim ditolak',
        selesai: 'Klaim selesai',
    };

    return labels[status] ?? status;
}
</script>

<template>
    <Head title="Pemeriksaan Garansi" />

    <div class="min-h-screen bg-[#F4F7FB] text-slate-800">
        <header class="bg-[#08264F] text-white shadow-sm">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#FF6B16] font-black">
                        SMA
                    </div>

                    <div>
                        <h1 class="font-bold">
                            Sumber Material Abadi
                        </h1>
                        <p class="text-xs text-blue-200">
                            Layanan Pelanggan
                        </p>
                    </div>
                </a>

                <span class="hidden rounded-full border border-white/20 px-3 py-1.5 text-xs font-medium sm:inline-flex">
                    Garansi Digital
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-16">
            <section class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-[#08264F]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        class="h-8 w-8"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3 19 6v5c0 4.5-3 7.5-7 10-4-2.5-7-5.5-7-10V6l7-3Z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m9 12 2 2 4-4"
                        />
                    </svg>
                </div>

                <p class="mt-5 text-sm font-semibold uppercase tracking-wider text-[#FF6B16]">
                    Layanan Purna Jual
                </p>

                <h2 class="mt-2 text-3xl font-bold tracking-tight text-[#08264F] sm:text-4xl">
                    Periksa Garansi Produk
                </h2>

                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">
                    Masukkan nomor garansi pada struk pembelian untuk melihat
                    produk, periode garansi, dan status klaimnya.
                </p>
            </section>

            <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <form @submit.prevent="cariGaransi">
                    <label
                        for="nomor-garansi"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Nomor Garansi
                    </label>

                    <input
                        id="nomor-garansi"
                        v-model="nomor"
                        type="text"
                        required
                        maxlength="80"
                        autocomplete="off"
                        placeholder="Contoh: GAR-20261028-AB12CD34"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm uppercase outline-none transition placeholder:normal-case placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                    />

                    <button
                        type="submit"
                        class="mt-4 w-full rounded-xl bg-[#FF6B16] px-5 py-3.5 text-sm font-bold text-white transition hover:bg-orange-600"
                    >
                        Periksa Garansi
                    </button>
                </form>
            </section>

            <section
                v-if="dicari && hasil"
                class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-700">
                            Nomor garansi ditemukan
                        </p>

                        <h3 class="mt-1 break-all text-lg font-bold text-[#08264F]">
                            {{ hasil.no_garansi }}
                        </h3>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-bold"
                        :class="
                            hasil.masa_berlaku === 'aktif'
                                ? 'bg-green-50 text-green-700'
                                : 'bg-slate-100 text-slate-600'
                        "
                    >
                        {{
                            hasil.masa_berlaku === 'aktif'
                                ? 'Masa garansi aktif'
                                : 'Masa garansi berakhir'
                        }}
                    </span>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div>
                        <p class="text-sm text-slate-500">
                            Nama produk
                        </p>
                        <p class="mt-1 font-semibold text-slate-800">
                            {{ hasil.nama_produk }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">
                                Tanggal mulai
                            </p>
                            <p class="mt-1 font-semibold text-[#08264F]">
                                {{ hasil.tanggal_mulai }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">
                                Tanggal berakhir
                            </p>
                            <p class="mt-1 font-semibold text-[#08264F]">
                                {{ hasil.tanggal_berakhir }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <p class="text-sm text-slate-500">
                            Status klaim
                        </p>
                        <p class="mt-1 font-semibold text-[#08264F]">
                            {{ statusKlaim(hasil.status_klaim) }}
                        </p>
                    </div>

                    <p class="text-xs leading-5 text-slate-500">
                        Simpan nomor garansi ini untuk keperluan layanan purna jual.
                        Status masa berlaku dan status klaim merupakan dua informasi
                        yang berbeda.
                    </p>
                </div>
            </section>

            <section
                v-else-if="dicari && !hasil"
                role="alert"
                class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-5 sm:p-6"
            >
                <h3 class="font-bold text-orange-900">
                    Nomor garansi tidak ditemukan
                </h3>

                <p class="mt-2 text-sm leading-6 text-orange-800">
                    Periksa kembali nomor yang tertulis pada struk pembelian.
                    Pastikan tidak ada karakter yang terlewat.
                </p>
            </section>

            <p class="mt-8 text-center text-xs leading-5 text-slate-400">
                Sumber Material Abadi · Layanan pemeriksaan garansi digital
            </p>
        </main>
    </div>
</template>
