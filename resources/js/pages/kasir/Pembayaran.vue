
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

interface Props {
    id: number
    no_invoice: string
    total_bayar: number | string
    status: string
    qris_url: string | null
    payment_expires_at: string | null
}

interface PaymentStatusResponse {
    no_invoice: string
    status: string
    payment_expires_at: string | null
}

const props = defineProps<Props>()

// Status pembayaran
const paymentStatus = ref(props.status)
const expiresAt = ref(props.payment_expires_at)
const currentTime = ref(Date.now())
const pollingError = ref('')

// Timer
let pollingTimer: number | undefined
let countdownTimer: number | undefined
let isPolling = false

// Memeriksa apakah transaksi masih menunggu pembayaran
const isPending = computed(() => {
    return paymentStatus.value === 'menunggu_pembayaran'
})

// Mengubah total pembayaran ke format angka
const totalAmount = computed(() => {
    const amount = Number(props.total_bayar)

    return Number.isFinite(amount) ? amount : null
})

// Format Rupiah
const formattedTotal = computed(() => {
    if (totalAmount.value === null) {
        return 'Total tidak tersedia'
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(totalAmount.value)
})

// Menghitung countdown pembayaran
const countdown = computed(() => {
    if (!expiresAt.value) {
        return '--:--'
    }

    const expiryTime = Date.parse(expiresAt.value)

    if (!Number.isFinite(expiryTime)) {
        return '--:--'
    }

    const difference = Math.max(
        0,
        Math.ceil((expiryTime - currentTime.value) / 1000)
    )

    const minutes = Math.floor(difference / 60)
    const seconds = difference % 60

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

// Memeriksa apakah waktu QRIS sudah habis
const timeHasExpired = computed(() => {
    if (!expiresAt.value) {
        return false
    }

    const expiryTime = Date.parse(expiresAt.value)

    return Number.isFinite(expiryTime)
        && expiryTime <= currentTime.value
})

// Label status pembayaran
const statusLabel = computed(() => {
    const labels: Record<string, string> = {
        menunggu_pembayaran: 'Menunggu Pembayaran',
        lunas: 'Pembayaran Berhasil',
        kedaluwarsa: 'Pembayaran Kedaluwarsa',
        gagal: 'Pembayaran Gagal',
        dibatalkan: 'Pembayaran Dibatalkan',
    }

    return labels[paymentStatus.value] ?? 'Status Tidak Diketahui'
})

// Warna badge status
const statusClass = computed(() => {
    if (paymentStatus.value === 'lunas') {
        return 'bg-green-100 text-green-700'
    }

    if (paymentStatus.value === 'menunggu_pembayaran') {
        return 'bg-orange-100 text-orange-700'
    }

    return 'bg-red-100 text-red-700'
})

// Mengambil status pembayaran terbaru dari Laravel
async function checkPaymentStatus() {
    if (!isPending.value || isPolling) {
        return
    }

    isPolling = true

    try {
        const response = await fetch(
            `/kasir/pembayaran/${encodeURIComponent(props.no_invoice)}/status`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            }
        )

        if (!response.ok) {
            throw new Error('Gagal memeriksa status pembayaran.')
        }

        const data: PaymentStatusResponse = await response.json()

        if (typeof data.status === 'string') {
            paymentStatus.value = data.status
        }

        if ('payment_expires_at' in data) {
            expiresAt.value = data.payment_expires_at
        }

        pollingError.value = ''

    } catch (error) {
        pollingError.value = error instanceof Error
            ? error.message
            : 'Tidak dapat menghubungi server.'

    } finally {
        isPolling = false
    }

    // Hentikan polling jika status sudah berubah
    if (!isPending.value && pollingTimer !== undefined) {
        window.clearInterval(pollingTimer)
        pollingTimer = undefined
    }
}

// Menjalankan timer ketika halaman dibuka
onMounted(() => {
    countdownTimer = window.setInterval(() => {
        currentTime.value = Date.now()
    }, 1000)

    // Periksa status setiap 5 detik
    if (isPending.value) {
        pollingTimer = window.setInterval(() => {
            void checkPaymentStatus()
        }, 5000)

        // Periksa langsung ketika halaman dibuka
        void checkPaymentStatus()
    }
})

// Membersihkan timer ketika halaman ditutup
onBeforeUnmount(() => {
    if (pollingTimer !== undefined) {
        window.clearInterval(pollingTimer)
    }

    if (countdownTimer !== undefined) {
        window.clearInterval(countdownTimer)
    }
})
</script>

<template>
    <Head :title="`Pembayaran ${props.no_invoice}`" />

    <div class="min-h-screen bg-[#F4F7FB] px-4 py-8">
        <div class="mx-auto max-w-2xl">

            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/kasir"
                    class="text-sm font-medium text-[#08264F] hover:text-orange-600"
                >
                    ← Kembali ke POS
                </Link>

                <h1 class="mt-4 text-2xl font-bold text-[#08264F]">
                    Pembayaran QRIS
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Silakan selesaikan pembayaran untuk transaksi ini.
                </p>
            </div>

            <!-- Payment Card -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">

                <!-- Informasi Transaksi -->
                <div class="border-b border-gray-100 p-6 text-center">
                    <p class="text-sm text-gray-500">
                        Nomor Invoice
                    </p>

                    <p class="mt-1 font-semibold text-[#08264F]">
                        {{ props.no_invoice }}
                    </p>

                    <p class="mt-5 text-sm text-gray-500">
                        Total Pembayaran
                    </p>

                    <p class="mt-1 text-3xl font-bold text-[#08264F]">
                        {{ formattedTotal }}
                    </p>

                    <!-- Status -->
                    <span
                        class="mt-4 inline-flex rounded-full px-4 py-2 text-xs font-semibold"
                        :class="statusClass"
                    >
                        {{ statusLabel }}
                    </span>
                </div>

                <!-- Menunggu Pembayaran -->
                <div
                    v-if="isPending"
                    class="p-6 text-center"
                >
                    <template v-if="props.qris_url">

                        <h2 class="text-lg font-bold text-[#08264F]">
                            Scan QR Code
                        </h2>

                        <p class="mt-2 text-sm text-gray-500">
                            Gunakan simulator QRIS Midtrans Sandbox
                            untuk menyimulasikan pembayaran.
                        </p>

                        <!-- QR Code -->
                        <div
                            class="mx-auto mt-5 flex max-w-xs justify-center rounded-xl border border-gray-200 bg-white p-3"
                        >
                            <img
                                :src="props.qris_url"
                                alt="QRIS Pembayaran"
                                class="h-auto w-full object-contain"
                            />
                        </div>

                        <!-- Countdown -->
                        <div class="mt-5 rounded-xl bg-orange-50 p-4">
                            <p class="text-sm font-medium text-orange-800">
                                Selesaikan pembayaran sebelum
                            </p>

                            <p class="mt-1 text-2xl font-bold text-orange-700">
                                {{ countdown }}
                            </p>
                        </div>

                        <!-- Midtrans Simulator -->
                        <a
                            href="https://simulator.sandbox.midtrans.com/openapi/qris/index"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-5 inline-flex rounded-lg bg-[#08264F] px-5 py-3 text-sm font-semibold text-white hover:bg-blue-900"
                        >
                            Buka Simulator Midtrans
                        </a>

                        <!-- Informasi Sandbox -->
                        <p class="mt-4 text-xs leading-relaxed text-gray-500">
                            Ini adalah transaksi Sandbox.
                            Jangan gunakan aplikasi mobile banking
                            atau dompet digital sungguhan untuk
                            memindai QR Sandbox.
                        </p>

                        <!-- Waktu Habis -->
                        <p
                            v-if="timeHasExpired"
                            class="mt-4 text-sm font-medium text-orange-700"
                        >
                            Waktu QR telah habis. Sistem sedang menunggu
                            konfirmasi status dari server Midtrans.
                        </p>
                    </template>

                    <!-- QR Tidak Tersedia -->
                    <div
                        v-else
                        class="rounded-xl bg-orange-50 p-5"
                    >
                        <p class="font-semibold text-orange-800">
                            QR Code belum tersedia.
                        </p>

                        <p class="mt-1 text-sm text-orange-700">
                            Silakan periksa kembali atau hubungi administrator.
                        </p>
                    </div>

                    <!-- Automatic Polling -->
                    <p class="mt-5 animate-pulse text-xs text-gray-500">
                        Memeriksa status pembayaran otomatis...
                    </p>

                    <p
                        v-if="pollingError"
                        class="mt-2 text-xs text-red-600"
                    >
                        {{ pollingError }}
                    </p>
                </div>

                <!-- Pembayaran Berhasil -->
                <div
                    v-else-if="paymentStatus === 'lunas'"
                    class="p-8 text-center"
                >
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-600"
                    >
                        ✓
                    </div>

                    <h2 class="mt-4 text-xl font-bold text-green-700">
                        Pembayaran Berhasil!
                    </h2>

                    <p class="mt-2 text-sm text-gray-500">
                        Transaksi sudah dikonfirmasi oleh sistem.
                    </p>

                    <Link
                        :href="`/kasir/struk/${encodeURIComponent(props.no_invoice)}`"
                        class="mt-6 inline-flex rounded-lg bg-[#16803C] px-5 py-3 text-sm font-semibold text-white hover:bg-green-700"
                    >
                        Lihat Struk
                    </Link>
                </div>

                <!-- Pembayaran Gagal / Kedaluwarsa / Dibatalkan -->
                <div
                    v-else
                    class="p-8 text-center"
                >
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-3xl text-red-600"
                    >
                        !
                    </div>

                    <h2 class="mt-4 text-xl font-bold text-red-700">
                        {{ statusLabel }}
                    </h2>

                    <p class="mt-2 text-sm text-gray-500">
                        Transaksi ini tidak berhasil diselesaikan.
                    </p>

                    <Link
                        href="/kasir"
                        class="mt-6 inline-flex rounded-lg bg-[#08264F] px-5 py-3 text-sm font-semibold text-white hover:bg-blue-900"
                    >
                        Kembali ke POS
                    </Link>
                </div>

            </div>

            <!-- Footer -->
            <p class="mt-5 text-center text-xs text-gray-400">
                Sumber Material Abadi · Sistem POS
            </p>

        </div>
    </div>
</template>
