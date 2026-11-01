<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garansi;
use App\Models\RiwayatKlaimGaransi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KlaimGaransiController extends Controller
{
    /**
     * Memastikan hanya admin yang dapat mengakses fitur ini.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403
        );
    }

    /**
     * Menampilkan daftar klaim garansi.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'semua');

        $query = Garansi::query()
            ->with([
                'detail.transaksi',
                'riwayat.user:id,name',
            ])
            ->where('status_klaim', '!=', 'belum_ada');

        // Filter berdasarkan status klaim.
        if ($status !== 'semua') {
            $query->where('status_klaim', $status);
        }

        // Pencarian nomor garansi, keluhan, produk, atau pelanggan.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_garansi', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%")
                    ->orWhereHas('detail', function ($detail) use ($search) {
                        $detail->where(
                            'nama_produk',
                            'like',
                            "%{$search}%"
                        )->orWhereHas('transaksi', function ($transaksi) use ($search) {
                            $transaksi->where(
                                'nama_pelanggan',
                                'like',
                                "%{$search}%"
                            );
                        });
                    });
            });
        }

        $klaim = $query
            ->orderByDesc('tanggal_klaim')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Garansi $item) {
                return [
                    'id' => $item->id,
                    'no_garansi' => $item->no_garansi,

                    'nama_produk' => $item->detail?->nama_produk
                        ?? 'Produk tidak tersedia',

                    'nama_pelanggan' => $item->detail?->transaksi?->nama_pelanggan
                        ?? 'Pelanggan umum',

                    'no_hp_pelanggan' => $item->detail?->transaksi?->no_hp_pelanggan,

                    'keluhan' => $item->keluhan,

                    'foto_url' => $item->foto_klaim
                        ? asset('storage/' . ltrim($item->foto_klaim, '/'))
                        : null,

                    'tanggal_klaim' => $item->tanggal_klaim?->format(
                        'd/m/Y H:i'
                    ),

                    'status_klaim' => $item->status_klaim,
                    'catatan_admin' => $item->catatan_admin,
                    'catatan_pelanggan' => $item->catatan_pelanggan,

                    'jenis_penyelesaian' => $item->jenis_penyelesaian,
                    'detail_penyelesaian' => $item->detail_penyelesaian,

                    'nominal_refund' => $item->nominal_refund === null
                        ? null
                        : (int) $item->nominal_refund,

                    'metode_refund' => $item->metode_refund,
                    'referensi_refund' => $item->referensi_refund,

                    'tanggal_selesai' => $item->tanggal_selesai?->format(
                        'd/m/Y H:i'
                    ),

                    'maksimal_refund' => (int) (
                        $item->detail?->subtotal ?? 0
                    ),

                    // Riwayat aktivitas klaim.
                    'riwayat' => $item->riwayat
                        ->sortBy('created_at')
                        ->values()
                        ->map(fn ($log) => [
                            'id' => $log->id,
                            'jenis_aksi' => $log->jenis_aksi,
                            'aktor' => $log->aktor,

                            'nama_aktor' => $log->user?->name
                                ?? ($log->aktor === 'pelanggan'
                                    ? 'Pelanggan'
                                    : 'Sistem'),

                            'tanggal' => $log->created_at?->format(
                                'd/m/Y H:i'
                            ),

                            'status_sebelumnya' => $log->status_sebelumnya,
                            'status_baru' => $log->status_baru,
                            'catatan' => $log->catatan,

                            'jenis_penyelesaian' => $log->jenis_penyelesaian,
                            'detail_penyelesaian' => $log->detail_penyelesaian,
                            'nominal_refund' => $log->nominal_refund,
                            'metode_refund' => $log->metode_refund,
                            'referensi_refund' => $log->referensi_refund,
                        ])
                        ->all(),
                ];
            });

        return Inertia::render('admin/klaim-garansi/Index', [
            'klaim' => $klaim,

            'filters' => [
                'q' => $search,
                'status' => $status,
            ],

            'stats' => [
                'total' => Garansi::where(
                    'status_klaim',
                    '!=',
                    'belum_ada'
                )->count(),

                'diajukan' => Garansi::where(
                    'status_klaim',
                    'diajukan'
                )->count(),

                'diverifikasi' => Garansi::where(
                    'status_klaim',
                    'diverifikasi'
                )->count(),

                'disetujui' => Garansi::where(
                    'status_klaim',
                    'disetujui'
                )->count(),

                'ditolak' => Garansi::where(
                    'status_klaim',
                    'ditolak'
                )->count(),
            ],
        ]);
    }

    /**
     * Memproses perubahan status dan keputusan klaim.
     */
    public function updateStatus(
        Request $request,
        Garansi $garansi
    ): RedirectResponse {
        $this->authorizeAdmin($request);

        $isApproval = $request->input('status') === 'disetujui';

        $isRefund = $isApproval
            && $request->input('jenis_penyelesaian') === 'refund';

        $isBankTransfer = $isRefund
            && $request->input('metode_refund') === 'transfer';

        // Validasi input keputusan admin.
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'diverifikasi',
                    'disetujui',
                    'ditolak',
                    'selesai',
                ]),
            ],

            'catatan_admin' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'catatan_pelanggan' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf(
                    $request->input('status') === 'ditolak'
                ),
            ],

            'jenis_penyelesaian' => [
                'nullable',
                Rule::requiredIf($isApproval),
                Rule::in([
                    'perbaikan',
                    'penggantian',
                    'refund',
                ]),
            ],

            'detail_penyelesaian' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf($isApproval),
            ],

            'nominal_refund' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf($isRefund),
            ],

            'metode_refund' => [
                'nullable',
                Rule::requiredIf($isRefund),
                Rule::in([
                    'tunai',
                    'transfer',
                ]),
            ],

            'referensi_refund' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf($isBankTransfer),
            ],
        ]);

        $adminId = $request->user()->id;

        DB::transaction(function () use (
            $garansi,
            $validated,
            $adminId
        ) {
            // Ambil data garansi dengan penguncian transaksi.
            $item = Garansi::query()
                ->whereKey($garansi->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Alur perubahan status yang diperbolehkan.
            $transitions = [
                'diajukan' => ['diverifikasi'],
                'diverifikasi' => ['disetujui', 'ditolak'],
                'disetujui' => ['selesai'],
                'ditolak' => [],
                'selesai' => [],
                'belum_ada' => [],
            ];

            $currentStatus = $item->status_klaim;
            $nextStatus = $validated['status'];

            $allowedStatuses = $transitions[$currentStatus] ?? [];

            if (! in_array($nextStatus, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Perubahan status klaim tidak diizinkan.',
                ]);
            }

            $updates = [
                'status_klaim' => $nextStatus,
            ];

            // Simpan catatan admin jika diberikan.
            if (array_key_exists('catatan_admin', $validated)) {
                $updates['catatan_admin'] =
                    $validated['catatan_admin'];
            }

            if (array_key_exists('catatan_pelanggan', $validated)) {
                $updates['catatan_pelanggan'] =
                    $validated['catatan_pelanggan'];
            }

            // Ketika klaim disetujui, tentukan penyelesaiannya.
            if ($nextStatus === 'disetujui') {
                $detail = $item->detail()->first();

                if (! $detail) {
                    throw ValidationException::withMessages([
                        'status' => 'Detail transaksi untuk garansi ini tidak ditemukan.',
                    ]);
                }

                $jenis = $validated['jenis_penyelesaian'];

                $updates['jenis_penyelesaian'] = $jenis;
                $updates['detail_penyelesaian'] =
                    $validated['detail_penyelesaian'];

                if ($jenis === 'refund') {
                    $nominalRefund = (int) $validated['nominal_refund'];
                    $maksimalRefund = (int) $detail->subtotal;

                    // Refund tidak boleh melebihi nilai pembelian.
                    if ($nominalRefund > $maksimalRefund) {
                        throw ValidationException::withMessages([
                            'nominal_refund' =>
                                'Nominal refund tidak boleh melebihi '
                                . 'nilai pembelian sebesar Rp'
                                . number_format($maksimalRefund, 0, ',', '.')
                                . '.',
                        ]);
                    }

                    $updates['nominal_refund'] = $nominalRefund;
                    $updates['metode_refund'] = $validated['metode_refund'];

                    $updates['referensi_refund'] =
                        $validated['referensi_refund'] ?? null;
                } else {
                    // Kosongkan data refund untuk perbaikan/penggantian.
                    $updates['nominal_refund'] = null;
                    $updates['metode_refund'] = null;
                    $updates['referensi_refund'] = null;
                }
            }

            // Klaim hanya dapat selesai setelah disetujui.
            if ($nextStatus === 'selesai') {
                if (
                    ! $item->jenis_penyelesaian
                    || ! $item->detail_penyelesaian
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Informasi penyelesaian klaim belum lengkap.',
                    ]);
                }

                if (
                    $item->jenis_penyelesaian === 'refund'
                    && (
                        ! $item->nominal_refund
                        || ! $item->metode_refund
                        || (
                            $item->metode_refund === 'transfer'
                            && ! $item->referensi_refund
                        )
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Informasi refund belum lengkap.',
                    ]);
                }

                // Tanggal penyelesaian dikonfirmasi admin.
                $updates['tanggal_selesai'] = now();
            }

            // Simpan perubahan pada data garansi.
            $item->update($updates);

            // Tentukan jenis aktivitas untuk audit trail.
            $jenisAksi = match ($nextStatus) {
                'diverifikasi' => 'verifikasi',
                'disetujui', 'ditolak' => 'keputusan',
                'selesai' => 'penyelesaian',
                default => 'perubahan_status',
            };

            // Simpan riwayat aktivitas admin.
            RiwayatKlaimGaransi::create([
                'garansi_id' => $item->id,
                'jenis_aksi' => $jenisAksi,
                'aktor' => 'admin',
                'user_id' => $adminId,

                'status_sebelumnya' => $currentStatus,
                'status_baru' => $nextStatus,

                'catatan' => $validated['catatan_pelanggan']
                    ?? $validated['catatan_admin']
                    ?? $validated['detail_penyelesaian']
                    ?? null,

                'jenis_penyelesaian' => $item->jenis_penyelesaian,
                'detail_penyelesaian' => $item->detail_penyelesaian,
                'nominal_refund' => $item->nominal_refund,
                'metode_refund' => $item->metode_refund,
                'referensi_refund' => $item->referensi_refund,
            ]);
        });

        return redirect()
            ->route('admin.klaim-garansi.index')
            ->with(
                'success',
                'Status dan informasi penyelesaian klaim berhasil diperbarui.'
            );
    }
}
