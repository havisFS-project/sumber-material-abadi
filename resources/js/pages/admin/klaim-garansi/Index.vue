<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type ClaimStatus =
    | 'diajukan'
    | 'diverifikasi'
    | 'disetujui'
    | 'ditolak'
    | 'selesai';

type ResolutionType =
    | 'perbaikan'
    | 'penggantian'
    | 'refund';

type RefundMethod = 'tunai' | 'transfer';

type ClaimHistoryItem = {
    id: number;
    jenis_aksi: string;
    aktor: string;
    nama_aktor: string;
    tanggal: string | null;
    status_sebelumnya: ClaimStatus | null;
    status_baru: ClaimStatus;
    catatan: string | null;
    jenis_penyelesaian: ResolutionType | null;
    detail_penyelesaian: string | null;
    nominal_refund: number | null;
    metode_refund: RefundMethod | null;
    referensi_refund: string | null;
};

type ClaimItem = {
    id: number;
    no_garansi: string;
    nama_produk: string;
    nama_pelanggan: string;
    no_hp_pelanggan: string | null;
    keluhan: string | null;
    foto_url: string | null;
    tanggal_klaim: string | null;
    status_klaim: ClaimStatus;
    catatan_admin: string | null;
    catatan_pelanggan: string | null;
    jenis_penyelesaian: ResolutionType | null;
    detail_penyelesaian: string | null;
    nominal_refund: number | null;
    metode_refund: RefundMethod | null;
    referensi_refund: string | null;
    tanggal_selesai: string | null;
    maksimal_refund: number;
    riwayat: ClaimHistoryItem[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    klaim: {
        data: ClaimItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q: string;
        status: string;
    };
    stats: {
        total: number;
        diajukan: number;
        diverifikasi: number;
        disetujui: number;
        ditolak: number;
    };
}>();

const search = ref(props.filters.q ?? '');
const statusFilter = ref(props.filters.status ?? 'semua');

const selected = ref<ClaimItem | null>(null);

const reviewForm = useForm({
    status: '' as ClaimStatus | '',
    catatan_admin: '',
    catatan_pelanggan: '',

    jenis_penyelesaian: '' as ResolutionType | '',
    detail_penyelesaian: '',

    nominal_refund: null as number | null,
    metode_refund: '' as RefundMethod | '',
    referensi_refund: '',
});

const statusLabels: Record<ClaimStatus, string> = {
    diajukan: 'Diajukan',
    diverifikasi: 'Diverifikasi',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    selesai: 'Selesai',
};

const availableStatus = computed(() => {
    if (!selected.value) return [];

    const transitions: Record<ClaimStatus, {
        value: ClaimStatus;
        label: string;
    }[]> = {
        diajukan: [
            { value: 'diverifikasi', label: 'Tandai Diverifikasi' },
        ],
        diverifikasi: [
            { value: 'disetujui', label: 'Setujui Klaim' },
            { value: 'ditolak', label: 'Tolak Klaim' },
        ],
        disetujui: [
            { value: 'selesai', label: 'Tandai Selesai' },
        ],
        ditolak: [],
        selesai: [],
    };

    return transitions[selected.value.status_klaim];
});

const needsReason = computed(
    () => reviewForm.status === 'ditolak',
);

const isApproval = computed(
    () => reviewForm.status === 'disetujui',
);

const isRefund = computed(
    () =>
        isApproval.value &&
        reviewForm.jenis_penyelesaian === 'refund',
);

function rupiah(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatStatus(status: ClaimStatus) {
    return statusLabels[status];
}

function applyFilters() {
    router.get(
        '/admin/klaim-garansi',
        {
            q: search.value.trim() || undefined,
            status: statusFilter.value === 'semua'
                ? undefined
                : statusFilter.value,
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
    statusFilter.value = 'semua';

    router.get('/admin/klaim-garansi', {}, {
        preserveState: true,
        preserveScroll: true,
    });
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

function openReview(item: ClaimItem) {
    selected.value = item;

    reviewForm.reset();
    reviewForm.clearErrors();

    // Isi pilihan status berdasarkan status klaim saat ini.
    reviewForm.status =
        availableStatus.value[0]?.value ?? '';

    // Ambil informasi penyelesaian yang sudah pernah disimpan.
    reviewForm.jenis_penyelesaian =
        item.jenis_penyelesaian ?? '';

    reviewForm.detail_penyelesaian =
        item.detail_penyelesaian ?? '';

    reviewForm.nominal_refund =
        item.nominal_refund;

    reviewForm.metode_refund =
        item.metode_refund ?? '';

    reviewForm.referensi_refund =
        item.referensi_refund ?? '';

    reviewForm.catatan_admin =
        item.catatan_admin ?? '';

    reviewForm.catatan_pelanggan =
    item.catatan_pelanggan ?? '';
}

function closeReview() {
    if (reviewForm.processing) return;

    selected.value = null;
    reviewForm.reset();
    reviewForm.clearErrors();
}


function submitReview() {
    if (!selected.value || !reviewForm.status) {
        return;
    }

    reviewForm.clearErrors();

    // Alasan penolakan wajib diberikan.
    if (
        needsReason.value &&
        !reviewForm.catatan_pelanggan.trim()
    ) {
        reviewForm.setError(
            'catatan_pelanggan',
            'Alasan penolakan untuk pelanggan wajib diisi.',
        );
        return;
    }

    // Validasi informasi penyelesaian.
    if (isApproval.value) {
        if (!reviewForm.jenis_penyelesaian) {
            reviewForm.setError(
                'jenis_penyelesaian',
                'Pilih jenis penyelesaian klaim.',
            );
            return;
        }

        if (!reviewForm.detail_penyelesaian.trim()) {
            reviewForm.setError(
                'detail_penyelesaian',
                'Detail penyelesaian wajib diisi.',
            );
            return;
        }
    }

    // Validasi khusus refund.
    if (isRefund.value) {
        const nominal = Number(reviewForm.nominal_refund);

        if (!Number.isInteger(nominal) || nominal < 1) {
            reviewForm.setError(
                'nominal_refund',
                'Masukkan nominal refund yang valid.',
            );
            return;
        }

        if (nominal > selected.value.maksimal_refund) {
            reviewForm.setError(
                'nominal_refund',
                `Nominal maksimal refund adalah ${rupiah(selected.value.maksimal_refund)}.`,
            );
            return;
        }

        if (!reviewForm.metode_refund) {
            reviewForm.setError(
                'metode_refund',
                'Pilih metode pengembalian dana.',
            );
            return;
        }

        if (
            reviewForm.metode_refund === 'transfer' &&
            !reviewForm.referensi_refund.trim()
        ) {
            reviewForm.setError(
                'referensi_refund',
                'Nomor referensi transfer wajib diisi.',
            );
            return;
        }
    }

    const action = reviewForm.status === 'ditolak'
        ? 'menolak'
        : reviewForm.status === 'disetujui'
            ? 'menyetujui'
            : 'memperbarui';

    const confirmed = window.confirm(
        `Yakin ingin ${action} klaim ${selected.value.no_garansi}?`,
    );

    if (!confirmed) return;

    reviewForm.patch(
        `/admin/klaim-garansi/${selected.value.id}/status`,
        {
            preserveScroll: true,

            onSuccess: () => {
                selected.value = null;
                reviewForm.reset();
            },
        },
    );
}

</script>

<template>
    <Head title="Manajemen Klaim Garansi" />

    <div class="space-y-6">
        <section class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    LAYANAN PURNA JUAL
                </p>

                <h2 class="mt-1 text-2xl font-bold text-[#08264F] sm:text-3xl">
                    Klaim Garansi
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Periksa pengajuan pelanggan dan kelola keputusan garansi.
                </p>
            </div>

            <Link
                href="/garansi"
                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Lihat Halaman Publik
            </Link>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Pengajuan</p>
                <p class="mt-3 text-3xl font-bold text-[#08264F]">
                    {{ stats.total }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Menunggu Pemeriksaan</p>
                <p class="mt-3 text-3xl font-bold text-[#FF6B16]">
                    {{ stats.diajukan }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Diverifikasi</p>
                <p class="mt-3 text-3xl font-bold text-blue-700">
                    {{ stats.diverifikasi }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Disetujui</p>
                <p class="mt-3 text-3xl font-bold text-green-700">
                    {{ stats.disetujui }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Ditolak</p>
                <p class="mt-3 text-3xl font-bold text-red-600">
                    {{ stats.ditolak }}
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Daftar Pengajuan
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Tinjau keluhan, foto bukti, dan status klaim.
                        </p>
                    </div>

                    <span class="text-sm text-slate-500">
                        {{ klaim.total }} pengajuan
                    </span>
                </div>

                <form
                    class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]"
                    @submit.prevent="applyFilters"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor garansi, produk, atau pelanggan..."
                        class="w-full min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                    />

                    <select
                        v-model="statusFilter"
                        aria-label="Filter status klaim"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="semua">Semua status</option>
                        <option value="diajukan">Diajukan</option>
                        <option value="diverifikasi">Diverifikasi</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="selesai">Selesai</option>
                    </select>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#08264F] px-5 py-3 text-sm font-semibold text-white hover:bg-[#10386C]"
                    >
                        Cari
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-4 font-semibold">Garansi / Produk</th>
                            <th class="px-5 py-4 font-semibold">Pelanggan</th>
                            <th class="px-5 py-4 font-semibold">Tanggal Pengajuan</th>
                            <th class="px-5 py-4 font-semibold">Bukti Foto</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="item in klaim.data"
                            :key="item.id"
                            class="transition hover:bg-slate-50/70"
                        >
                            <td class="px-5 py-4">
                                <p class="break-all text-sm font-semibold text-[#08264F]">
                                    {{ item.no_garansi }}
                                </p>

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ item.nama_produk }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ item.nama_pelanggan }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ item.no_hp_pelanggan || 'Tanpa nomor HP' }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ item.tanggal_klaim ?? '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <a
                                    v-if="item.foto_url"
                                    :href="item.foto_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#08264F] hover:text-[#FF6B16]"
                                >
                                    <img
                                        :src="item.foto_url"
                                        alt="Foto bukti klaim"
                                        class="h-12 w-12 rounded-lg border border-slate-200 object-cover"
                                        loading="lazy"
                                    />
                                    Lihat foto
                                </a>

                                <span v-else class="text-sm text-slate-400">
                                    Tidak ada foto
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-orange-50 text-orange-700': item.status_klaim === 'diajukan',
                                        'bg-blue-50 text-blue-700': item.status_klaim === 'diverifikasi',
                                        'bg-green-50 text-green-700': item.status_klaim === 'disetujui',
                                        'bg-red-50 text-red-700': item.status_klaim === 'ditolak',
                                        'bg-slate-100 text-slate-600': item.status_klaim === 'selesai',
                                    }"
                                >
                                    {{ formatStatus(item.status_klaim) }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#08264F] transition hover:border-[#FF6B16] hover:text-[#FF6B16]"
                                    @click="openReview(item)"
                                >
                                    Periksa
                                </button>
                            </td>
                        </tr>

                        <tr v-if="klaim.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <p class="font-semibold text-slate-700">
                                    Belum ada pengajuan klaim
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    Pengajuan pelanggan akan muncul di halaman ini.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
                <p class="text-sm text-slate-500">
                    Halaman {{ klaim.current_page }} dari {{ klaim.last_page }}
                </p>

                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in klaim.links"
                        :key="link.label"
                        type="button"
                        :disabled="!link.url"
                        class="min-w-9 rounded-lg border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
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

        <!-- Dialog pemeriksaan klaim -->
        <div
            v-if="selected"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
            @click.self="closeReview"
        >
            <section class="my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#FF6B16]">
                            Pemeriksaan Pengajuan
                        </p>

                        <h3 class="mt-1 text-lg font-bold text-[#08264F]">
                            Detail Klaim Garansi
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100"
                        aria-label="Tutup"
                        :disabled="reviewForm.processing"
                        @click="closeReview"
                    >
                        ×
                    </button>
                </header>

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-slate-500">Nomor Garansi</p>
                            <p class="mt-1 break-all text-sm font-bold text-[#08264F]">
                                {{ selected.no_garansi }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Tanggal Pengajuan</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">
                                {{ selected.tanggal_klaim ?? '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Produk</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">
                                {{ selected.nama_produk }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Pelanggan</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">
                                {{ selected.nama_pelanggan }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">
                            Keluhan Pelanggan
                        </p>

                        <div class="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                            {{ selected.keluhan || 'Tidak ada keluhan.' }}
                        </div>
                    </div>

                    <div v-if="selected.foto_url">
                        <p class="mb-2 text-sm font-semibold text-slate-700">
                            Foto Bukti Kerusakan
                        </p>

                        <a
                            :href="selected.foto_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                :src="selected.foto_url"
                                alt="Foto bukti kerusakan produk"
                                class="max-h-72 w-full rounded-xl border border-slate-200 bg-slate-50 object-contain"
                            />
                        </a>
                    </div>

                    <!-- Catatan internal admin -->
                    <div
                        v-if="selected.catatan_admin"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <p class="font-bold text-amber-900">
                            Catatan Internal Admin
                        </p>

                        <p class="mt-2 whitespace-pre-line text-sm text-amber-900">
                            {{ selected.catatan_admin }}
                        </p>

                        <p class="mt-2 text-xs text-amber-700">
                            Catatan ini hanya dapat dilihat oleh admin.
                        </p>
                    </div>

                    <!-- Pesan yang ditampilkan kepada pelanggan -->
                    <div
                        v-if="selected.catatan_pelanggan"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <p class="font-bold text-blue-900">
                            Pesan untuk Pelanggan
                        </p>

                        <p class="mt-2 whitespace-pre-line text-sm text-blue-900">
                            {{ selected.catatan_pelanggan }}
                        </p>
                    </div>

                    <div
                        v-if="availableStatus.length > 0"
                        class="space-y-4 border-t border-slate-100 pt-5"
                    >
                        <h4 class="font-bold text-[#08264F]">
                            Keputusan Admin
                        </h4>

                        <div>
                            <label
                                for="status-klaim"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Status Berikutnya
                            </label>

                            <select
                                id="status-klaim"
                                v-model="reviewForm.status"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            >
                                <option
                                    v-for="option in availableStatus"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <p
                                v-if="reviewForm.errors.status"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ reviewForm.errors.status }}
                            </p>
                        </div>

                        <!-- Pilihan penyelesaian saat klaim disetujui -->
                        <div
                            v-if="isApproval"
                            class="space-y-4 rounded-xl border border-orange-200 bg-orange-50/50 p-4"
                        >
                            <div>
                                <h4 class="font-bold text-[#08264F]">
                                    Jenis Penyelesaian
                                </h4>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Tentukan bentuk solusi yang akan diberikan kepada pelanggan.
                                </p>
                            </div>

                            <div>
                                <label
                                    for="jenis-penyelesaian"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Pilih penyelesaian
                                </label>

                                <select
                                    id="jenis-penyelesaian"
                                    v-model="reviewForm.jenis_penyelesaian"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                >
                                    <option value="">Pilih jenis penyelesaian</option>
                                    <option value="perbaikan">Perbaikan Barang</option>
                                    <option value="penggantian">Penggantian Barang</option>
                                    <option value="refund">Pengembalian Dana (Refund)</option>
                                </select>

                                <p
                                    v-if="reviewForm.errors.jenis_penyelesaian"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ reviewForm.errors.jenis_penyelesaian }}
                                </p>
                            </div>

                            <div v-if="reviewForm.jenis_penyelesaian">
                                <label
                                    for="detail-penyelesaian"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Detail Penyelesaian
                                </label>

                                <textarea
                                    id="detail-penyelesaian"
                                    v-model="reviewForm.detail_penyelesaian"
                                    rows="3"
                                    maxlength="2000"
                                    :placeholder="
                                        reviewForm.jenis_penyelesaian === 'perbaikan'
                                            ? 'Contoh: Komponen produk telah diperbaiki dan diuji.'
                                            : reviewForm.jenis_penyelesaian === 'penggantian'
                                            ? 'Contoh: Produk diganti dengan barang baru dengan spesifikasi setara.'
                                            : 'Jelaskan dasar dan proses pengembalian dana.'
                                    "
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                ></textarea>

                                <p
                                    v-if="reviewForm.errors.detail_penyelesaian"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ reviewForm.errors.detail_penyelesaian }}
                                </p>
                            </div>

                            <!-- Informasi tambahan jika memilih refund -->
                            <div
                                v-if="isRefund"
                                class="space-y-4 border-t border-orange-200 pt-4"
                            >
                                <div class="rounded-xl bg-white p-3">
                                    <p class="text-xs text-slate-500">
                                        Maksimal pengembalian dana
                                    </p>

                                    <p class="mt-1 text-lg font-bold text-[#08264F]">
                                        {{ rupiah(selected?.maksimal_refund ?? 0) }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="nominal-refund"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Nominal Refund (Rp)
                                    </label>

                                    <input
                                        id="nominal-refund"
                                        v-model.number="reviewForm.nominal_refund"
                                        type="number"
                                        min="1"
                                        :max="selected?.maksimal_refund ?? 0"
                                        step="1"
                                        placeholder="Masukkan nominal refund"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                    />

                                    <p
                                        v-if="reviewForm.errors.nominal_refund"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ reviewForm.errors.nominal_refund }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="metode-refund"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Metode Pengembalian
                                    </label>

                                    <select
                                        id="metode-refund"
                                        v-model="reviewForm.metode_refund"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                    >
                                        <option value="">Pilih metode</option>
                                        <option value="tunai">Tunai</option>
                                        <option value="transfer">Transfer Bank</option>
                                    </select>

                                    <p
                                        v-if="reviewForm.errors.metode_refund"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ reviewForm.errors.metode_refund }}
                                    </p>
                                </div>

                                <div v-if="reviewForm.metode_refund === 'transfer'">
                                    <label
                                        for="referensi-refund"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Nomor Referensi Transfer
                                    </label>

                                    <input
                                        id="referensi-refund"
                                        v-model="reviewForm.referensi_refund"
                                        type="text"
                                        maxlength="100"
                                        placeholder="Nomor referensi bukti transfer"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                                    />

                                    <p
                                        v-if="reviewForm.errors.referensi_refund"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ reviewForm.errors.referensi_refund }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan internal admin -->
                        <div>
                            <label
                                for="catatan-admin"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Catatan Internal Admin
                                <span class="font-normal text-slate-400">
                                    (opsional, hanya untuk admin)
                                </span>
                            </label>

                            <textarea
                                id="catatan-admin"
                                v-model="reviewForm.catatan_admin"
                                rows="3"
                                maxlength="2000"
                                placeholder="Catatan pemeriksaan internal..."
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            ></textarea>

                            <p
                                v-if="reviewForm.errors.catatan_admin"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ reviewForm.errors.catatan_admin }}
                            </p>
                        </div>

                        <!-- Pesan untuk pelanggan -->
                        <div>
                            <label
                                for="catatan-pelanggan"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Pesan untuk Pelanggan

                                <span v-if="needsReason" class="text-red-500">*</span>

                                <span v-else class="font-normal text-slate-400">
                                    (opsional)
                                </span>
                            </label>

                            <textarea
                                id="catatan-pelanggan"
                                v-model="reviewForm.catatan_pelanggan"
                                rows="3"
                                maxlength="2000"
                                placeholder="Tuliskan alasan penolakan atau informasi yang perlu diketahui pelanggan..."
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                            ></textarea>

                            <p
                                v-if="reviewForm.errors.catatan_pelanggan"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ reviewForm.errors.catatan_pelanggan }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Pesan ini akan ditampilkan pada halaman pemeriksaan garansi pelanggan.
                            </p>
                        </div>
                    </div>

                    <!-- Informasi penyelesaian yang sudah tersimpan -->
                    <div
                        v-if="selected.jenis_penyelesaian"
                        class="space-y-3 rounded-xl border border-green-200 bg-green-50 p-4"
                    >
                        <h4 class="font-bold text-green-900">
                            Rencana Penyelesaian Klaim
                        </h4>

                        <div>
                            <p class="text-xs text-green-800">Jenis penyelesaian</p>
                            <p class="mt-1 font-semibold text-green-950">
                                {{
                                    selected.jenis_penyelesaian === 'perbaikan'
                                        ? 'Perbaikan Barang'
                                        : selected.jenis_penyelesaian === 'penggantian'
                                            ? 'Penggantian Barang'
                                            : 'Pengembalian Dana (Refund)'
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-green-800">Detail penyelesaian</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-green-950">
                                {{ selected.detail_penyelesaian || '—' }}
                            </p>
                        </div>

                        <div v-if="selected.jenis_penyelesaian === 'refund'">
                            <p class="text-xs text-green-800">Nominal refund</p>
                            <p class="mt-1 font-bold text-green-950">
                                {{ rupiah(selected.nominal_refund ?? 0) }}
                            </p>

                            <p class="mt-2 text-xs text-green-800">Metode pengembalian</p>
                            <p class="mt-1 text-sm font-semibold text-green-950">
                                {{ selected.metode_refund === 'transfer' ? 'Transfer Bank' : 'Tunai' }}
                            </p>

                            <template v-if="selected.referensi_refund">
                                <p class="mt-2 text-xs text-green-800">Referensi transfer</p>
                                <p class="mt-1 break-all text-sm font-semibold text-green-950">
                                    {{ selected.referensi_refund }}
                                </p>
                            </template>
                        </div>

                        <div v-if="selected.tanggal_selesai">
                            <p class="text-xs text-green-800">Tanggal selesai</p>
                            <p class="mt-1 text-sm font-semibold text-green-950">
                                {{ selected.tanggal_selesai }}
                            </p>
                        </div>
                    </div>

                    <!-- Pesan untuk klaim yang tidak memiliki tindakan lanjutan -->
                    <div
                        v-if="availableStatus.length === 0"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
                    >
                        Klaim ini sudah mencapai status akhir dan tidak memiliki tindakan lanjutan.
                    </div>

                    <!-- Riwayat Aktivitas -->
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="mb-4">
                            <h4 class="font-bold text-[#08264F]">
                                Riwayat Aktivitas
                            </h4>
                            <p class="mt-1 text-xs text-slate-500">
                                Catatan tindakan pelanggan dan admin dari awal pengajuan hingga penyelesaian.
                            </p>
                        </div>

                        <template v-if="selected.riwayat && selected.riwayat.length > 0">
                            <div class="space-y-4">
                                <div
                                    v-for="log in selected.riwayat"
                                    :key="log.id"
                                    class="relative border-l-2 border-orange-300 pb-1 pl-5 last:border-transparent"
                                >
                                    <span class="absolute -left-[7px] top-0 h-3 w-3 rounded-full border-2 border-white bg-[#FF6B16]"></span>

                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-bold capitalize text-[#08264F]">
                                            {{ log.jenis_aksi.replaceAll('_', ' ') }}
                                        </p>
                                        <span class="text-xs text-slate-400">
                                            {{ log.tanggal ?? '—' }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Oleh: {{ log.nama_aktor }}
                                    </p>

                                    <p class="mt-2 text-sm text-slate-700">
                                        <template v-if="log.status_sebelumnya">
                                            {{ formatStatus(log.status_sebelumnya) }}
                                            →
                                        </template>
                                        <template v-else>
                                            Belum ada →
                                        </template>
                                        {{ formatStatus(log.status_baru) }}
                                    </p>

                                    <p
                                        v-if="log.catatan"
                                        class="mt-2 whitespace-pre-line text-sm leading-5 text-slate-600"
                                    >
                                        {{ log.catatan }}
                                    </p>

                                    <div
                                        v-if="log.jenis_penyelesaian"
                                        class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-600"
                                    >
                                        <p class="font-semibold text-[#08264F]">
                                            {{
                                                log.jenis_penyelesaian === 'perbaikan'
                                                    ? 'Perbaikan Barang'
                                                    : log.jenis_penyelesaian === 'penggantian'
                                                        ? 'Penggantian Barang'
                                                        : 'Pengembalian Dana (Refund)'
                                            }}
                                        </p>
                                        <p class="mt-1 whitespace-pre-line">
                                            {{ log.detail_penyelesaian }}
                                        </p>

                                        <p v-if="log.nominal_refund !== null" class="mt-2 font-semibold">
                                            Refund: {{ rupiah(log.nominal_refund) }}
                                        </p>
                                        <p v-if="log.metode_refund" class="mt-1">
                                            Metode: {{ log.metode_refund === 'transfer' ? 'Transfer Bank' : 'Tunai' }}
                                        </p>
                                        <p v-if="log.referensi_refund" class="mt-1 break-all">
                                            Referensi: {{ log.referensi_refund }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <p v-else class="text-sm text-slate-500">
                            Belum ada riwayat aktivitas untuk klaim ini.
                        </p>
                    </div>
                </div>

                <footer class="flex flex-col-reverse justify-end gap-3 border-t border-slate-100 bg-slate-50 p-5 sm:flex-row">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        :disabled="reviewForm.processing"
                        @click="closeReview"
                    >
                        Tutup
                    </button>

                    <button
                        v-if="availableStatus.length > 0"
                        type="button"
                        class="rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="reviewForm.processing || !reviewForm.status"
                        @click="submitReview"
                    >
                        {{
                            reviewForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Keputusan'
                        }}
                    </button>
                </footer>
            </section>
        </div>
    </div>
</template>
