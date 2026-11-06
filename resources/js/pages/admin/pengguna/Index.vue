<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type Pengguna = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    users: {
        data: Pengguna[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
    };
    summary: {
        total: number;
        active: number;
        inactive: number;
    };
}>();

// Pencarian pengguna.
const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(
        '/admin/pengguna',
        {
            search: search.value.trim() || undefined,
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
        '/admin/pengguna',
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

// Pagination.
const showingFrom = computed(() => {
    if (props.users.total === 0) return 0;

    return (props.users.current_page - 1) * 10 + 1;
});

const showingTo = computed(() => {
    return Math.min(props.users.current_page * 10, props.users.total);
});

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

// Form tambah kasir.
const showModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function openModal() {
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    form.post('/admin/pengguna', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
}

// Mengubah status akun kasir.
function changeStatus(user: Pengguna) {
    const nextStatus = !user.is_active;

    const action = nextStatus
        ? 'mengaktifkan'
        : 'menonaktifkan';

    const explanation = nextStatus
        ? 'Kasir dapat login dan menggunakan aplikasi kembali.'
        : 'Kasir tidak dapat login dan sesi aktifnya akan ditolak saat mengakses halaman berikutnya.';

    const confirmed = window.confirm(
        `Yakin ingin ${action} akun "${user.name}"?\n\n${explanation}\n\nRiwayat transaksi tetap disimpan.`,
    );

    if (!confirmed) return;

    router.patch(
        `/admin/pengguna/${user.id}/status`,
        {},
        {
            preserveScroll: true,
        },
    );
}

// Format tanggal.
function formatDate(date: string | null) {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <div class="space-y-6">

        <!-- Header -->
        <section class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    MANAJEMEN PENGGUNA
                </p>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#08264F] sm:text-3xl">
                    Data Kasir
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Kelola akun, hak akses, dan status pengguna kasir toko.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    Manajemen akun aktif
                </span>

                <button
                    type="button"
                    class="rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                    @click="openModal"
                >
                    + Tambah Kasir
                </button>
            </div>
        </section>

        <!-- Statistik -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Total Kasir
                    </p>

                    <span class="rounded-xl bg-blue-50 px-3 py-2 text-lg text-[#08264F]">
                        ♙
                    </span>
                </div>

                <p class="mt-4 text-3xl font-bold text-[#08264F]">
                    {{ summary.total }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Semua akun kasir terdaftar
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Kasir Aktif
                    </p>

                    <span class="rounded-xl bg-green-50 px-3 py-2 text-lg text-green-700">
                        ✓
                    </span>
                </div>

                <p class="mt-4 text-3xl font-bold text-green-700">
                    {{ summary.active }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Dapat login ke aplikasi
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">
                        Kasir Nonaktif
                    </p>

                    <span class="rounded-xl bg-red-50 px-3 py-2 text-lg text-red-600">
                        —
                    </span>
                </div>

                <p class="mt-4 text-3xl font-bold text-red-600">
                    {{ summary.inactive }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Tidak dapat login ke aplikasi
                </p>
            </div>
        </section>

        <!-- Daftar Kasir -->
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-lg font-bold text-[#08264F]">
                            Daftar Pengguna Kasir
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Kelola akun kasir dan atur status akses aplikasi.
                        </p>
                    </div>

                    <span class="text-sm text-slate-500">
                        {{ users.total }} akun kasir
                    </span>
                </div>

                <!-- Pencarian -->
                <form
                    class="mt-5 flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="applyFilters"
                >
                    <label class="relative block flex-1">
                        <span class="sr-only">Cari kasir</span>

                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            ⌕
                        </span>

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari nama atau email kasir..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-9 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                        />
                    </label>

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

            <!-- Tabel -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] border-collapse text-left">

                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 font-semibold">Pengguna</th>
                            <th class="px-5 py-4 font-semibold">Email</th>
                            <th class="px-5 py-4 font-semibold">Bergabung</th>
                            <th class="px-5 py-4 font-semibold">Role</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="transition hover:bg-slate-50/70"
                        >
                            <!-- Nama -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg font-bold uppercase text-[#08264F]">
                                        {{ user.name.charAt(0) }}
                                    </div>

                                    <div>
                                        <p class="font-semibold text-slate-800">
                                            {{ user.name }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            ID Pengguna: #{{ user.id }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ user.email }}
                            </td>

                            <!-- Tanggal -->
                            <td class="px-5 py-4 text-sm text-slate-500">
                                {{ formatDate(user.created_at) }}
                            </td>

                            <!-- Role -->
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-[#08264F]">
                                    Kasir
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        user.is_active
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-red-50 text-red-600'
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            user.is_active
                                                ? 'bg-green-500'
                                                : 'bg-red-500'
                                        "
                                    ></span>

                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="px-6 py-4">
                                <button
                                    type="button"
                                    class="inline-flex rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                    :class="
                                        user.is_active
                                            ? 'border-red-200 text-red-600 hover:bg-red-50'
                                            : 'border-green-200 text-green-700 hover:bg-green-50'
                                    "
                                    @click="changeStatus(user)"
                                >
                                    {{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </td>
                        </tr>

                        <!-- Data Kosong -->
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="text-3xl text-slate-300">
                                        ♙
                                    </div>

                                    <p class="mt-3 font-semibold text-slate-700">
                                        Kasir tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Coba ubah kata kunci pencarian atau tambahkan akun kasir baru.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-6">

                <p class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-semibold text-slate-700">
                        {{ showingFrom }}–{{ showingTo }}
                    </span>
                    dari
                    <span class="font-semibold text-slate-700">
                        {{ users.total }}
                    </span>
                    kasir
                </p>

                <div class="flex flex-wrap items-center gap-1">
                    <button
                        v-for="link in users.links"
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

    <!-- Modal Tambah Kasir -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">

                <!-- Header Modal -->
                <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-sm font-medium text-[#FF6B16]">
                            MANAJEMEN PENGGUNA
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-[#08264F]">
                            Tambah Akun Kasir
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Buat akun baru untuk pengguna kasir toko.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup modal"
                        @click="closeModal"
                    >
                        ×
                    </button>
                </div>

                <!-- Form -->
                <form class="space-y-5 p-6" @submit.prevent="submitForm">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Lengkap
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Rina Amelia"
                            autocomplete="name"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                            :class="{ 'border-red-400': form.errors.name }"
                        />

                        <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Alamat Email
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="kasir@email.com"
                            autocomplete="email"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                            :class="{ 'border-red-400': form.errors.email }"
                        />

                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Password
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="Minimal 8 karakter"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                            :class="{ 'border-red-400': form.errors.password }"
                        />

                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Konfirmasi Password
                        </label>

                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Masukkan ulang password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:bg-white focus:ring-2 focus:ring-orange-100"
                        />
                    </div>

                    <!-- Informasi Role -->
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="text-lg text-[#08264F]">
                                ℹ
                            </span>

                            <div>
                                <p class="text-sm font-semibold text-[#08264F]">
                                    Hak Akses Kasir
                                </p>

                                <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                    Akun yang dibuat otomatis memiliki role kasir dan status aktif. Admin dapat mengubah status akun kapan saja.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            :disabled="form.processing"
                            @click="closeModal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-[#FF6B16] px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Akun Kasir' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </Teleport>
</template>
