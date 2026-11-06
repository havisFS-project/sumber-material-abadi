<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

type HasilGaransi = {
    no_garansi: string;
    nama_produk: string;
    tanggal_mulai: string;
    tanggal_berakhir: string;
    masa_berlaku: 'aktif' | 'berakhir' | 'tidak_valid';
    status_klaim: string;
    catatan_pelanggan: string | null;
    jenis_penyelesaian: 'perbaikan' | 'penggantian' | 'refund' | null;
    detail_penyelesaian: string | null;
    nominal_refund: number | null;
    metode_refund: 'tunai' | 'transfer' | null;
    tanggal_selesai: string | null;
};

const props = defineProps<{
    dicari: boolean;
    noGaransi: string;
    hasil: HasilGaransi | null;
    whatsappToko?: string | null;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}>();

const nomor = ref(props.noGaransi);

const whatsappUrl = computed(() => {
    const phone = (props.whatsappToko ?? '').replace(/\D/g, '');

    if (!phone || !props.hasil) return null;

    const pesan = `Halo Sumber Material Abadi, saya ingin menanyakan tindak lanjut klaim garansi nomor ${props.hasil.no_garansi}. Mohon informasi lebih lanjut mengenai keputusan klaim saya. Terima kasih.`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(pesan)}`;
});

const claimForm = useForm({
    no_garansi: props.hasil?.no_garansi ?? props.noGaransi,
    keluhan: '',
    foto_klaim: null as File | null,
});

// Perbarui nomor garansi ketika hasil pencarian berubah.
watch(
    () => props.hasil?.no_garansi,
    (value) => {
        claimForm.no_garansi = value ?? '';
    },
);

function pilihFoto(event: Event) {
    const input = event.target as HTMLInputElement;
    claimForm.foto_klaim = input.files?.[0] ?? null;
}

function ajukanKlaim() {
    if (!props.hasil) return;

    claimForm.no_garansi = props.hasil.no_garansi;

    claimForm.post('/garansi/klaim', {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            claimForm.reset('keluhan', 'foto_klaim');
        },
    });
}

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

function jenisPenyelesaianLabel(jenis: string): string {
    const labels: Record<string, string> = {
        perbaikan: 'Perbaikan Barang',
        penggantian: 'Penggantian Barang',
        refund: 'Pengembalian Dana (Refund)',
    };

    return labels[jenis] ?? jenis;
}

function rupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
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
            <div
                v-if="props.flash?.success"
                role="status"
                class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800"
            >
                {{ props.flash.success }}
            </div>

            <div
                v-if="props.flash?.error"
                role="alert"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800"
            >
                {{ props.flash.error }}
            </div>

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
                                : hasil.masa_berlaku === 'berakhir'
                                    ? 'bg-amber-50 text-amber-700'
                                    : 'bg-red-50 text-red-700'
                        "
                    >
                        {{
                            hasil.masa_berlaku === 'aktif'
                                ? 'Masa garansi aktif'
                                : hasil.masa_berlaku === 'berakhir'
                                    ? 'Masa garansi berakhir'
                                    : 'Garansi tidak berlaku'
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

                    <!-- Informasi perkembangan klaim -->
                    <div
                        v-if="hasil.status_klaim !== 'belum_ada'"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <h4 class="font-bold text-[#08264F]">
                            Informasi Pengajuan
                        </h4>

                        <p class="mt-2 text-sm leading-6 text-slate-700">
                            {{
                                hasil.status_klaim === 'diajukan'
                                    ? 'Pengajuan klaim berhasil diterima dan sedang menunggu pemeriksaan admin.'
                                    : hasil.status_klaim === 'diverifikasi'
                                    ? 'Admin sedang memeriksa kelayakan klaim dan bukti kerusakan.'
                                    : hasil.status_klaim === 'disetujui'
                                        ? 'Klaim Anda telah disetujui. Toko sedang menindaklanjuti penyelesaian.'
                                        : hasil.status_klaim === 'ditolak'
                                        ? 'Mohon maaf, klaim Anda belum dapat disetujui. Silakan hubungi pihak toko untuk informasi lebih lanjut.'
                                        : 'Penyelesaian klaim Anda telah selesai.'
                            }}
                        </p>
                    </div>

                    <!-- Kontak WhatsApp khusus klaim ditolak -->
                    <div
                        v-if="hasil.status_klaim === 'ditolak'"
                        class="rounded-xl border border-green-200 bg-green-50 p-4"
                    >
                        <h4 class="font-bold text-green-900">
                            Butuh penjelasan lebih lanjut?
                        </h4>

                        <p class="mt-2 text-sm leading-6 text-green-800">
                            Silakan hubungi toko melalui WhatsApp untuk
                            menanyakan alasan penolakan atau tindak lanjut
                            klaim garansi Anda.
                        </p>

                        <a
                            v-if="whatsappUrl"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700 sm:w-auto"
                        >
                            Hubungi WhatsApp Toko
                        </a>

                        <p
                            v-else
                            class="mt-3 text-xs leading-5 text-green-800"
                        >
                            Nomor WhatsApp toko belum dikonfigurasi.
                            Silakan hubungi toko melalui nomor resmi yang
                            tercantum pada struk pembelian.
                        </p>
                    </div>

                    <!-- Catatan yang ditujukan kepada pelanggan -->
                    <!-- Pesan yang ditujukan kepada pelanggan -->
                    <div
                        v-if="hasil.catatan_pelanggan"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <h4 class="font-bold text-[#08264F]">
                            Pesan dari Toko
                        </h4>

                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                            {{ hasil.catatan_pelanggan }}
                        </p>
                    </div>

                    <!-- Detail solusi yang diberikan -->
                    <div
                        v-if="hasil.jenis_penyelesaian"
                        class="space-y-4 rounded-xl border border-green-200 bg-green-50 p-4"
                    >
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-green-700">
                                Keputusan Penyelesaian
                            </p>

                            <h4 class="mt-1 text-lg font-bold text-green-900">
                                {{ jenisPenyelesaianLabel(hasil.jenis_penyelesaian) }}
                            </h4>
                        </div>

                        <div>
                            <p class="text-xs text-green-800">
                                Detail penyelesaian
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-green-950">
                                {{ hasil.detail_penyelesaian || '—' }}
                            </p>
                        </div>

                        <div v-if="hasil.jenis_penyelesaian === 'refund'">
                            <p class="text-xs text-green-800">
                                Nominal pengembalian dana
                            </p>

                            <p class="mt-1 text-xl font-bold text-green-900">
                                {{ rupiah(hasil.nominal_refund ?? 0) }}
                            </p>

                            <p class="mt-1 text-sm text-green-800">
                                Metode:
                                {{ hasil.metode_refund === 'transfer' ? 'Transfer Bank' : 'Tunai' }}
                            </p>

                            <p class="mt-2 text-xs leading-5 text-green-800">
                                Pengembalian dana dilakukan oleh pihak toko sesuai
                                informasi penyelesaian di atas.
                            </p>
                        </div>

                        <div
                            v-if="hasil.tanggal_selesai"
                            class="border-t border-green-200 pt-3"
                        >
                            <p class="text-xs text-green-800">
                                Tanggal penyelesaian
                            </p>

                            <p class="mt-1 text-sm font-semibold text-green-950">
                                {{ hasil.tanggal_selesai }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-slate-500">
                        Simpan nomor garansi ini untuk keperluan layanan purna jual.
                        Status masa berlaku dan status klaim merupakan dua informasi
                        yang berbeda.
                    </p>
                </div>
            </section>

            <section
                v-if="
                    dicari &&
                    hasil &&
                    hasil.masa_berlaku === 'aktif' &&
                    hasil.status_klaim === 'belum_ada'
                "
                class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
            >
                <div>
                    <p class="text-sm font-semibold text-[#FF6B16]">
                        LAYANAN PELANGGAN
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-[#08264F]">
                        Ajukan Klaim Garansi
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Jelaskan kendala produk dan sertakan foto sebagai bukti
                        untuk membantu admin melakukan pemeriksaan.
                    </p>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="ajukanKlaim">
                    <div>
                        <label
                            for="keluhan"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Keluhan atau kerusakan produk
                        </label>

                        <textarea
                            id="keluhan"
                            v-model="claimForm.keluhan"
                            rows="4"
                            minlength="10"
                            maxlength="2000"
                            required
                            placeholder="Jelaskan kondisi dan kendala yang dialami..."
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            :class="{
                                'border-red-400': claimForm.errors.keluhan,
                            }"
                        ></textarea>

                        <p
                            v-if="claimForm.errors.keluhan"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ claimForm.errors.keluhan }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Minimal 10 dan maksimal 2.000 karakter.
                        </p>
                    </div>

                    <div>
                        <label
                            for="foto_klaim"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Foto bukti kerusakan
                        </label>

                        <input
                            id="foto_klaim"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            required
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#08264F] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white"
                            @change="pilihFoto"
                        />

                        <p class="mt-2 text-xs text-slate-400">
                            Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.
                        </p>

                        <p
                            v-if="claimForm.foto_klaim"
                            class="mt-1 text-xs font-medium text-green-700"
                        >
                            File dipilih: {{ claimForm.foto_klaim.name }}
                        </p>

                        <p
                            v-if="claimForm.errors.foto_klaim"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ claimForm.errors.foto_klaim }}
                        </p>
                    </div>

                    <p
                        v-if="claimForm.errors.no_garansi"
                        role="alert"
                        class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                    >
                        {{ claimForm.errors.no_garansi }}
                    </p>

                    <button
                        type="submit"
                        :disabled="claimForm.processing"
                        class="w-full rounded-xl bg-[#FF6B16] px-5 py-3.5 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{
                            claimForm.processing
                                ? 'Mengirim Pengajuan...'
                                : 'Ajukan Klaim Garansi'
                        }}
                    </button>

                    <p class="text-center text-xs leading-5 text-slate-400">
                        Pengajuan akan diperiksa oleh admin. Pastikan informasi dan
                        foto yang diberikan sesuai kondisi produk.
                    </p>
                </form>
            </section>

            <section
                v-else-if="
                    dicari &&
                    hasil &&
                    hasil.masa_berlaku === 'tidak_valid'
                "
                class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 sm:p-6"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                            <path d="M10.3 3.9 1.8 18.1a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="font-bold text-red-900">
                            Garansi Tidak Berlaku
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-red-800">
                            Garansi produk ini sudah tidak berlaku karena
                            transaksi pembelian telah dibatalkan oleh pihak toko.
                            Oleh karena itu, pengajuan klaim garansi tidak dapat dilakukan.
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-else-if="
                    dicari &&
                    hasil &&
                    hasil.status_klaim !== 'belum_ada'
                "
                class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5"
            >
                <h3 class="font-bold text-[#08264F]">
                    Klaim sudah memiliki status
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Status saat ini:
                    <span class="font-semibold">
                        {{ statusKlaim(hasil.status_klaim) }}
                    </span>.
                    Silakan periksa kembali secara berkala untuk mengetahui
                    perkembangan klaim.
                </p>
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