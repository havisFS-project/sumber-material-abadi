<script setup lang="ts">
import {
    computed,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

const flash = computed(
    () =>
        ((page.props as Record<string, unknown>).flash ?? {}) as FlashMessages,
);

// Mengontrol apakah notifikasi sedang ditampilkan.
const showSuccess = ref(false);
const showError = ref(false);

// Menyimpan ID timer agar bisa dibatalkan.
let successTimer: ReturnType<typeof setTimeout> | undefined;
let errorTimer: ReturnType<typeof setTimeout> | undefined;

// Notifikasi sukses hilang otomatis setelah 4 detik.
watch(
    () => flash.value.success,
    (message) => {
        if (successTimer) {
            clearTimeout(successTimer);
        }

        showSuccess.value = Boolean(message);

        if (message) {
            successTimer = setTimeout(() => {
                showSuccess.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);

// Notifikasi error hilang otomatis setelah 4 detik.
watch(
    () => flash.value.error,
    (message) => {
        if (errorTimer) {
            clearTimeout(errorTimer);
        }

        showError.value = Boolean(message);

        if (message) {
            errorTimer = setTimeout(() => {
                showError.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);

// Bersihkan timer jika layout dihancurkan.
onBeforeUnmount(() => {
    if (successTimer) clearTimeout(successTimer);
    if (errorTimer) clearTimeout(errorTimer);
});

const activeMenu = computed(() => {
    if (page.url.startsWith('/admin/produk')) {
        return 'produk';
    }

    if (page.url.startsWith('/admin/kategori')) {
        return 'kategori';
    }

    return '';
});

function logout() {
    router.post('/logout');
}
</script>

<template>
    <Head title="Admin - Sumber Material Abadi" />

    <div class="min-h-screen bg-[#F4F7FB] text-slate-800">
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-[#08264F] text-white lg:flex"
        >
            <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#FF6B16] text-sm font-black"
                >
                    SMA
                </div>

                <div>
                    <p class="font-bold tracking-wide">
                        Sumber Material
                    </p>
                    <p class="text-xs text-blue-200">
                        Abadi · Admin Panel
                    </p>
                </div>
            </div>

            <nav class="flex-1 space-y-2 px-4 py-6">
                <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-[0.16em] text-blue-300">
                    Menu Utama
                </p>

                <Link
                    href="/dashboard"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-blue-100 transition hover:bg-white/10"
                >
                    <span class="text-lg">▦</span>
                    Dashboard
                </Link>

                <Link
                    href="/admin/produk"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition"
                    :class="
                        activeMenu === 'produk'
                            ? 'bg-[#FF6B16] text-white shadow-lg shadow-orange-950/20'
                            : 'text-blue-100 hover:bg-white/10'
                    "
                >
                    <span class="text-lg">▤</span>
                    Data Produk
                </Link>

                <Link
                    href="/admin/kategori"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition"
                    :class="
                        activeMenu === 'kategori'
                            ? 'bg-[#FF6B16] text-white shadow-lg shadow-orange-950/20'
                            : 'text-blue-100 hover:bg-white/10'
                    "
                >
                    <span class="text-lg">▦</span>
                    Kategori
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <Link
                    href="/"
                    class="mb-2 flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-blue-100 hover:bg-white/10"
                >
                    <span class="text-lg">↗</span>
                    Lihat Katalog
                </Link>

                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm text-blue-100 transition hover:bg-white/10"
                    @click="logout"
                >
                    <span class="text-lg">⇥</span>
                    Keluar
                </button>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header
                class="sticky top-0 z-20 flex min-h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-5 backdrop-blur sm:px-8"
            >
                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Sumber Material Abadi
                    </p>
                    <h1 class="text-lg font-bold text-[#08264F]">
                        Panel Administrasi
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 sm:inline-flex"
                    >
                        Administrator
                    </span>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 lg:hidden"
                        @click="logout"
                    >
                        Keluar
                    </button>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
                <div
                    v-if="flash.success && showSuccess"
                    role="status"
                    class="fixed left-4 right-4 top-20 z-50 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800 shadow-lg sm:left-auto sm:right-6 sm:w-[420px]"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100 font-bold">
                    ✓
                    </span>

                    {{ flash.success }}
                    <button
                        type="button"
                        class="ml-auto rounded-lg px-2 py-1 text-lg leading-none text-green-700 hover:bg-green-100"
                        aria-label="Tutup notifikasi"
                        @click="showSuccess = false"
                    >
                        ×
                    </button>
                </div>

                <div
                    v-if="flash.error && showError"
                    role="alert"
                    class="fixed left-4 right-4 top-20 z-50 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-lg sm:left-auto sm:right-6 sm:w-[420px]"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 font-bold">
                    !
                    </span>

                    {{ flash.error }}
                    <button
                        type="button"
                        class="ml-auto rounded-lg px-2 py-1 text-lg leading-none text-red-700 hover:bg-red-100"
                        aria-label="Tutup notifikasi"
                        @click="showError = false"
                    >
                        ×
                    </button>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>