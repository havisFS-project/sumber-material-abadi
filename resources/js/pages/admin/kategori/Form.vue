
<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type KategoriItem = {
    id: number;
    nama: string;
    slug: string;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    kategori: KategoriItem | null;
}>();

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    nama: props.kategori?.nama ?? '',
});

const pageTitle = computed(() =>
    isEdit.value ? 'Edit Kategori' : 'Tambah Kategori',
);

function submit() {
    if (isEdit.value && props.kategori) {
        form.put(`/admin/kategori/${props.kategori.id}`);
        return;
    }

    form.post('/admin/kategori');
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="mx-auto max-w-3xl space-y-6">
        <section class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    INVENTARIS TOKO
                </p>

                <h2 class="mt-1 text-2xl font-bold text-[#08264F] sm:text-3xl">
                    {{ pageTitle }}
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Atur pengelompokan produk toko material.
                </p>
            </div>

            <Link
                href="/admin/kategori"
                class="shrink-0 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Kembali
            </Link>
        </section>

        <form
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            @submit.prevent="submit"
        >
            <div class="space-y-5 p-5 sm:p-7">
                <div>
                    <label
                        for="nama"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Nama Kategori
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="nama"
                        v-model="form.nama"
                        type="text"
                        required
                        maxlength="100"
                        placeholder="Contoh: Cat, Semen, Besi, Peralatan"
                        autofocus
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        :class="{
                            'border-red-400 focus:border-red-400 focus:ring-red-100':
                                form.errors.nama,
                        }"
                    />

                    <p
                        v-if="form.errors.nama"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.nama }}
                    </p>

                    <p class="mt-2 text-xs text-slate-400">
                        Nama kategori harus unik. Slug akan dibuat otomatis oleh sistem.
                    </p>
                </div>

                <div
                    v-if="isEdit && kategori"
                    class="rounded-xl bg-slate-50 p-4"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Slug saat ini
                    </p>

                    <code class="mt-2 inline-block rounded-md bg-white px-2 py-1 text-sm text-[#08264F]">
                        {{ kategori.slug }}
                    </code>

                    <p class="mt-2 text-xs text-slate-500">
                        Slug akan disesuaikan otomatis setelah nama kategori diubah dan disimpan.
                    </p>
                </div>
            </div>

            <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-100 bg-slate-50/70 p-5 sm:flex-row sm:px-7">
                <Link
                    href="/admin/kategori"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50"
                >
                    Batal
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-[#FF6B16] px-6 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{
                        form.processing
                            ? 'Menyimpan...'
                            : isEdit
                              ? 'Simpan Perubahan'
                              : 'Tambah Kategori'
                    }}
                </button>
            </div>
        </form>
    </div>
</template>
