
<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type Kategori = {
    id: number;
    nama: string;
};

type ProdukAwal = {
    id: number;
    kategori_id: number;
    kode: string;
    nama: string;
    satuan: string;
    deskripsi: string | null;
    harga_jual: number;
    diskon_tipe: string | null;
    diskon_nilai: number;
    diskon_aktif: boolean;
    stok: number;
    punya_garansi: boolean;
    masa_garansi_bulan: number | null;
    is_active: boolean;
    foto: string | null;
    foto_url: string | null;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    produk: ProdukAwal | null;
    kategoris: Kategori[];
}>();

const isEdit = computed(() => props.mode === 'edit');

// Preview foto produk.
const fotoPreview = ref<string | null>(
    props.produk?.foto_url ?? null,
);

const form = useForm({
    kategori_id: props.produk
        ? String(props.produk.kategori_id)
        : '',
    kode: props.produk?.kode ?? '',
    nama: props.produk?.nama ?? '',
    satuan: props.produk?.satuan ?? '',
    deskripsi: props.produk?.deskripsi ?? '',
    harga_jual: props.produk?.harga_jual ?? 0,
    diskon_tipe: props.produk?.diskon_tipe ?? 'persen',
    diskon_nilai: props.produk?.diskon_nilai ?? 0,
    diskon_aktif: props.produk?.diskon_aktif ?? false,
    stok: props.produk?.stok ?? 0,
    punya_garansi: props.produk?.punya_garansi ?? false,
    masa_garansi_bulan: props.produk?.masa_garansi_bulan ?? 12,
    is_active: props.produk?.is_active ?? true,
    foto: null as File | null,
});

const pageTitle = computed(() =>
    isEdit.value ? 'Edit Produk' : 'Tambah Produk',
);

// Memilih foto dan membuat preview sementara.
function pilihFoto(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) return;

    form.foto = file;
    fotoPreview.value = URL.createObjectURL(file);
}

// Mengirim form produk.
function submit() {
    if (isEdit.value && props.produk) {
        // Laravel menerima method spoofing untuk upload multipart.
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/admin/produk/${props.produk.id}`, {
            forceFormData: true,
        });

        return;
    }

    form.post('/admin/produk', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="mx-auto max-w-4xl space-y-6">
        <section class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-[#FF6B16]">
                    INVENTARIS TOKO
                </p>

                <h2 class="mt-1 text-2xl font-bold text-[#08264F] sm:text-3xl">
                    {{ pageTitle }}
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Lengkapi informasi produk dengan benar.
                </p>
            </div>

            <Link
                href="/admin/produk"
                class="shrink-0 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Kembali
            </Link>
        </section>

        <form
            class="space-y-6"
            @submit.prevent="submit"
        >
            <!-- INFORMASI PRODUK -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h3 class="text-lg font-bold text-[#08264F]">
                    Informasi Produk
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Identitas dan kategori barang.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Kategori <span class="text-red-500">*</span>
                        </span>

                        <select
                            v-model="form.kategori_id"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        >
                            <option value="" disabled>
                                Pilih kategori
                            </option>

                            <option
                                v-for="item in kategoris"
                                :key="item.id"
                                :value="String(item.id)"
                            >
                                {{ item.nama }}
                            </option>
                        </select>

                        <p v-if="form.errors.kategori_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.kategori_id }}
                        </p>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Kode Produk <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model="form.kode"
                            required
                            maxlength="50"
                            placeholder="Contoh: CAT-021"
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.kode" class="mt-1 text-sm text-red-600">
                            {{ form.errors.kode }}
                        </p>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Produk <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model="form.nama"
                            required
                            maxlength="150"
                            placeholder="Contoh: Cat Tembok Putih 5 kg"
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.nama" class="mt-1 text-sm text-red-600">
                            {{ form.errors.nama }}
                        </p>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Satuan <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model="form.satuan"
                            required
                            maxlength="30"
                            placeholder="Contoh: kg, sak, batang, biji"
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.satuan" class="mt-1 text-sm text-red-600">
                            {{ form.errors.satuan }}
                        </p>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Harga Jual (Rp) <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model.number="form.harga_jual"
                            type="number"
                            min="0"
                            step="1"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.harga_jual" class="mt-1 text-sm text-red-600">
                            {{ form.errors.harga_jual }}
                        </p>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Deskripsi
                        </span>

                        <textarea
                            v-model="form.deskripsi"
                            rows="3"
                            maxlength="2000"
                            placeholder="Informasi tambahan tentang produk..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        ></textarea>

                        <p v-if="form.errors.deskripsi" class="mt-1 text-sm text-red-600">
                            {{ form.errors.deskripsi }}
                        </p>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Stok Awal <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model.number="form.stok"
                            type="number"
                            min="0"
                            step="1"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.stok" class="mt-1 text-sm text-red-600">
                            {{ form.errors.stok }}
                        </p>
                    </label>
                </div>
            </section>

            <!-- FOTO PRODUK -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h3 class="text-lg font-bold text-[#08264F]">
                    Foto Produk
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Unggah foto agar pelanggan dapat melihat produk di katalog.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-[200px_1fr]">
                    <!-- Preview gambar -->
                    <div class="flex min-h-[180px] items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                        <img
                            v-if="fotoPreview"
                            :src="fotoPreview"
                            :alt="`Foto ${form.nama || 'produk'}`"
                            class="h-52 w-full object-contain"
                        />

                        <div
                            v-else
                            class="flex flex-col items-center gap-2 p-5 text-center text-slate-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                class="h-12 w-12"
                                aria-hidden="true"
                            >
                                <rect x="3" y="3" width="18" height="18" rx="3" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <path d="m21 15-5-5L5 21" />
                            </svg>

                            <span class="text-xs">
                                Preview foto produk
                            </span>
                        </div>
                    </div>

                    <!-- Input upload -->
                    <div class="flex flex-col justify-center">
                        <label
                            for="foto-produk"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Pilih Foto Produk
                        </label>

                        <input
                            id="foto-produk"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#08264F] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white"
                            @change="pilihFoto"
                        />

                        <p class="mt-3 text-xs leading-5 text-slate-500">
                            Format JPG, JPEG, atau PNG, WEBP. Ukuran maksimal 2 MB.
                        </p>

                        <p
                            v-if="form.foto"
                            class="mt-2 text-xs font-medium text-green-700"
                        >
                            Foto dipilih: {{ form.foto.name }}
                        </p>

                        <p
                            v-else-if="isEdit && props.produk?.foto"
                            class="mt-2 text-xs text-slate-500"
                        >
                            Foto lama akan tetap digunakan jika tidak memilih foto baru.
                        </p>

                        <p
                            v-else
                            class="mt-2 text-xs text-slate-500"
                        >
                            Foto bersifat opsional.
                        </p>

                        <p
                            v-if="form.errors.foto"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ form.errors.foto }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- HARGA DAN DISKON -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h3 class="text-lg font-bold text-[#08264F]">
                    Harga dan Diskon
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Atur promo yang berlaku pada produk ini.
                </p>

                <label class="mt-5 flex items-center gap-3">
                    <input
                        v-model="form.diskon_aktif"
                        type="checkbox"
                        class="h-4 w-4 accent-[#FF6B16]"
                    />

                    <span class="text-sm font-medium text-slate-700">
                        Aktifkan diskon produk
                    </span>
                </label>

                <div
                    v-if="form.diskon_aktif"
                    class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2"
                >
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Jenis Diskon <span class="text-red-500">*</span>
                        </span>

                        <select
                            v-model="form.diskon_tipe"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        >
                            <option value="persen">Persentase (%)</option>
                            <option value="nominal">Nominal (Rp)</option>
                        </select>

                        <p v-if="form.errors.diskon_tipe" class="mt-1 text-sm text-red-600">
                            {{ form.errors.diskon_tipe }}
                        </p>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Nilai Diskon <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model.number="form.diskon_nilai"
                            type="number"
                            min="0"
                            :max="form.diskon_tipe === 'persen' ? 100 : undefined"
                            step="1"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p class="mt-1 text-xs text-slate-500">
                            <template v-if="form.diskon_tipe === 'persen'">
                                Masukkan angka 0–100.
                            </template>

                            <template v-else>
                                Masukkan jumlah potongan dalam rupiah.
                            </template>
                        </p>

                        <p v-if="form.errors.diskon_nilai" class="mt-1 text-sm text-red-600">
                            {{ form.errors.diskon_nilai }}
                        </p>
                    </label>
                </div>
            </section>

            <!-- GARANSI DAN STATUS -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h3 class="text-lg font-bold text-[#08264F]">
                    Garansi dan Status
                </h3>

                <label class="mt-5 flex items-center gap-3">
                    <input
                        v-model="form.punya_garansi"
                        type="checkbox"
                        class="h-4 w-4 accent-[#FF6B16]"
                    />

                    <span class="text-sm font-medium text-slate-700">
                        Produk memiliki garansi
                    </span>
                </label>

                <div v-if="form.punya_garansi" class="mt-5 max-w-sm">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">
                            Masa Garansi (bulan) <span class="text-red-500">*</span>
                        </span>

                        <input
                            v-model.number="form.masa_garansi_bulan"
                            type="number"
                            min="1"
                            max="120"
                            step="1"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#FF6B16] focus:ring-2 focus:ring-orange-100"
                        />

                        <p v-if="form.errors.masa_garansi_bulan" class="mt-1 text-sm text-red-600">
                            {{ form.errors.masa_garansi_bulan }}
                        </p>
                    </label>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 accent-[#16803C]"
                        />

                        <span class="text-sm font-medium text-slate-700">
                            Produk aktif dan dapat dijual
                        </span>
                    </label>
                </div>
            </section>

            <!-- TOMBOL AKSI -->
            <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <Link
                    href="/admin/produk"
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
                              : 'Tambah Produk'
                    }}
                </button>
            </div>
        </form>
    </div>
</template>
