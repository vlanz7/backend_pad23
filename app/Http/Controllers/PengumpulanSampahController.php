<?php

namespace App\Http\Controllers;

use App\Models\PengumpulanSampah;
use App\Models\DetailPengumpulanSampah;
use App\Models\Pembayaran;
use App\Models\Warga;
use App\Models\JenisSampah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengumpulanSampahController extends Controller
{
    private function catatLog(string $aksi, string $entitas, int $idEntitas, $dataLama = null, 
    $dataBaru = null, ?string $keterangan = null): void {
        LogAktivitas::create([
            'id_admin' => Auth::user()->id_admin,
            'aksi' => $aksi,
            'entitas' => $entitas,
            'id_entitas' => $idEntitas,
            'data_lama' => $dataLama === null ? null : json_encode($dataLama, JSON_UNESCAPED_UNICODE),
            'data_baru' => $dataBaru === null ? null : json_encode($dataBaru, JSON_UNESCAPED_UNICODE),
            'keterangan' => $keterangan,
        ]);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:Menunggu Dipilah,Menunggu Pembayaran,Lunas,Sedang Dipilah,Selesai Dipilah',
            'tanggal' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $limit = (int) $request->input('limit', 10);
        $query = PengumpulanSampah::with(['warga', 'pembayaran'])
            ->withSum('detailPengumpulanSampah as total_biaya', 'nilai')
            ->withCount('detailPengumpulanSampah');

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'Menunggu Dipilah') {
                $query->whereDoesntHave('detailPengumpulanSampah');
            } elseif ($status === 'Menunggu Pembayaran') {
                $query->whereHas('detailPengumpulanSampah')
                    ->whereDoesntHave('pembayaran', function ($q) {
                        $q->where('status', 'Lunas');
                    });
            } elseif ($status === 'Lunas') {
                $query->whereHas('pembayaran', function ($q) {
                    $q->where('status', 'Lunas');
                });
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }
        $pengumpulan = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id_pengumpulan_sampah')
            ->paginate($limit);
        $pengumpulan->getCollection()->transform(function ($item) {
            $pembayaran = $item->pembayaran;
            $item->total_biaya = (float) ($item->total_biaya ?? 0);
            $item->status_pembayaran = $pembayaran?->status;
            $item->status_ui = $pembayaran?->status === 'Lunas'
                ? 'Lunas'
                : ($item->detail_pengumpulan_sampah_count > 0
                    ? 'Menunggu Pembayaran'
                    : 'Menunggu Dipilah');
            $item->unsetRelation('pembayaran');
            return $item;
        });
        return response()->json([
            'status' => true,
            'message' => 'Daftar pengumpulan sampah berhasil diambil.',
            'data' => $pengumpulan->items(),
            'meta' => [
                'page' => $pengumpulan->currentPage(),
                'limit' => $pengumpulan->perPage(),
                'total_data' => $pengumpulan->total(),
                'total_page' => $pengumpulan->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:30',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }
        $nama = trim($request->nama);
        $nomorWa = trim($request->nomor_wa);
        $warga = Warga::where('nama', $nama)->where('nomor_wa', $nomorWa)->first();
        if (!$warga) {
            $pemilikNomor = Warga::where('nomor_wa', $nomorWa)->first();
            if ($pemilikNomor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nomor sudah dipakai, check pengaturan untuk melihat nama yang memakai nomor ini.',
                    'data' => [
                        'nomor_wa' => $nomorWa,
                        'nama_pemilik_nomor' => $pemilikNomor->nama,
                    ],
                ], 409);
            }
        }
        if ($warga && isset($warga->status) && $warga->status !== 'Aktif') {
            return response()->json([
                'status' => false,
                'message' => 'Warga tidak aktif.',
                'data' => null,
            ], 422);
        }
        $wargaSudahTerdaftar = $warga !== null;
        $hasil = DB::transaction(function () use ($warga, $nama, $nomorWa, $request) {
            // Jika belum ada warga dengan pasangan nama dan nomor tersebut, buat data warga baru.
            if (!$warga) {
                $warga = Warga::create([
                    'nama' => $nama,
                    'nomor_wa' => $nomorWa,
                    'status' => 'aktif',
                    'updated_by' => $request->user()->id_admin,
                ]);
                $this->catatLog(
                    'create',
                    'warga',
                    $warga->id_warga,
                    null,
                    $warga->toArray(),
                    'Membuat data warga baru melalui proses pengumpulan sampah.'
                );
            }
            $pengumpulan = PengumpulanSampah::create([
                'id_warga' => $warga->id_warga,
                'id_admin' => $request->user()->id_admin,
                'status' => 'Menunggu Dipilah',
                'tanggal' => now()->toDateString(),
            ]);
            $this->catatLog(
                'create',
                'pengumpulan_sampah',
                $pengumpulan->id_pengumpulan_sampah,
                null,
                $pengumpulan->toArray(),
                'Membuat pengumpulan sampah baru.'
            );
            return [
                'warga' => $warga,
                'pengumpulan' => $pengumpulan,
            ];
        });
        return response()->json([
            'status' => true,
            'message' => $wargaSudahTerdaftar ? 'Pengumpulan sampah berhasil dibuat menggunakan data warga terdaftar.' : 'Data warga baru dan pengumpulan sampah berhasil dibuat.',
            'data' => $hasil['pengumpulan']->load('warga'),
        ], 201);
    }

    public function show($id)
    {
        $pengumpulan = PengumpulanSampah::with([
            'warga',
            'detailPengumpulanSampah.jenisSampah',
            'pembayaran',
        ])->find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $totalBiaya = round((float) $pengumpulan->detailPengumpulanSampah->sum('nilai'), 2);
        $pembayaran = $pengumpulan->pembayaran;
        $data = $pengumpulan->toArray();
        $data['total_biaya'] = $totalBiaya;
        $data['status_pembayaran'] = $pembayaran?->status;
        $data['status_ui'] = $pembayaran?->status === 'Lunas'
            ? 'Lunas'
            : ($pengumpulan->detailPengumpulanSampah->isNotEmpty()
                ? 'Menunggu Pembayaran'
                : 'Menunggu Dipilah');
        $data['pembayaran'] = $pembayaran;
        return response()->json([
            'status' => true,
            'message' => 'Detail pengumpulan sampah berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function storeDetail(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_jenis_sampah' => 'required|integer|exists:jenis_sampah,id_jenis_sampah',
            'berat' => 'required|numeric|gt:0',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $pengumpulan = PengumpulanSampah::find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        if ($pengumpulan->pembayaran()->where('status', 'Lunas')->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Detail tidak dapat ditambahkan karena pembayaran sudah lunas.',
                'data' => null,
            ], 422);
        }
        $jenisSampah = JenisSampah::find($request->id_jenis_sampah);
        if (!$jenisSampah) {
            return response()->json([
                'status' => false,
                'message' => 'Jenis sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $berat = round((float) $request->berat, 2);
        $tarif = (float) $jenisSampah->tarif_jasa_pengelolaan;
        $nilai = round($berat * $tarif, 2);
        $detail = DB::transaction(function () use ($pengumpulan, $jenisSampah, $berat, $nilai, $request) {
            $detail = DetailPengumpulanSampah::create([
                'id_pengumpulan_sampah' => $pengumpulan->id_pengumpulan_sampah,
                'id_jenis_sampah' => $jenisSampah->id_jenis_sampah,
                'berat' => $berat,
                'nilai' => $nilai,
            ]);
            $this->catatLog(
                'create',
                'detail_pengumpulan_sampah',
                $detail->id_detail_pengumpulan_sampah,
                null,
                $detail->toArray(),
                'Menambahkan detail pengumpulan sampah.'
            );
            if ($pengumpulan->status !== 'Selesai Dipilah') {
                $dataLamaPengumpulan = $pengumpulan->toArray();
                $pengumpulan->update(['status' => 'Selesai Dipilah']);
                $this->catatLog(
                    'update',
                    'pengumpulan_sampah',
                    $pengumpulan->id_pengumpulan_sampah,
                    $dataLamaPengumpulan,
                    $pengumpulan->fresh()->toArray(),
                    'Status pengumpulan berubah menjadi Selesai Dipilah setelah detail sampah ditambahkan.'
                );
            }

            if (!$pengumpulan->pembayaran()->exists()) {
                $pembayaran = Pembayaran::create([
                    'id_pengumpulan_sampah' => $pengumpulan->id_pengumpulan_sampah,
                    'id_admin' => $request->user()->id_admin,
                    'jumlah_dibayar' => 0,
                    'tanggal_bayar' => null,
                    'status' => 'Menunggu Pembayaran',
                ]);

                $this->catatLog(
                    'create',
                    'pembayaran',
                    $pembayaran->id_pembayaran,
                    null,
                    $pembayaran->toArray(),
                    'Membuat catatan pembayaran saat detail sampah pertama ditambahkan.'
                );
            }
            return $detail;
        });
        return response()->json([
            'status' => true,
            'message' => 'Detail sampah berhasil ditambahkan. Status database pengumpulan menjadi Selesai Dipilah dan status tampilan menjadi Menunggu Pembayaran.',
            'data' => $detail->load('jenisSampah'),
        ], 201);
    }

    public function destroyDetail($id, $id_detail)
    {
        $pengumpulan = PengumpulanSampah::find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if ($pengumpulan->pembayaran()->where('status', 'Lunas')->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Detail tidak dapat dihapus karena pembayaran sudah lunas.',
                'data' => null,
            ], 422);
        }

        $detail = DetailPengumpulanSampah::where('id_pengumpulan_sampah', $id)
            ->where('id_detail_pengumpulan_sampah', $id_detail)
            ->first();

        if (!$detail) {
            return response()->json([
                'status' => false,
                'message' => 'Detail pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        DB::transaction(function () use ($pengumpulan, $detail) {
            $dataLamaDetail = $detail->toArray();
            $detail->delete();
            $this->catatLog(
                'delete',
                'detail_pengumpulan_sampah',
                $detail->id_detail_pengumpulan_sampah,
                $dataLamaDetail,
                null,
                'Menghapus detail pengumpulan sampah.'
            );

            if (!$pengumpulan->detailPengumpulanSampah()->exists()) {
                $pembayaran = $pengumpulan->pembayaran()->first();
                if ($pembayaran && $pembayaran->status !== 'Lunas') {
                    $dataLamaPembayaran = $pembayaran->toArray();
                    $idPembayaran = $pembayaran->id_pembayaran;
                    $pembayaran->delete();
                    $this->catatLog(
                        'delete',
                        'pembayaran',
                        $idPembayaran,
                        $dataLamaPembayaran,
                        null,
                        'Menghapus catatan pembayaran karena semua detail sampah dihapus.'
                    );
                }
                // Jika seluruh detail dihapus sebelum lunas, kembalikan ke status awal.
                if ($pengumpulan->status !== 'Menunggu Dipilah') {
                    $dataLamaPengumpulan = $pengumpulan->toArray();
                    $pengumpulan->update(['status' => 'Menunggu Dipilah']);
                    $this->catatLog(
                        'update',
                        'pengumpulan_sampah',
                        $pengumpulan->id_pengumpulan_sampah,
                        $dataLamaPengumpulan,
                        $pengumpulan->fresh()->toArray(),
                        'Status dikembalikan menjadi Menunggu Dipilah karena semua detail dihapus.'
                    );
                }
            }
        });
        return response()->json([
            'status' => true,
            'message' => 'Detail pengumpulan sampah berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function storePembayaran($id, $request)
    {
        $pengumpulan = PengumpulanSampah::with('detailPengumpulanSampah')->find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        if ($pengumpulan->detailPengumpulanSampah->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Pembayaran tidak dapat ditandai lunas karena belum ada detail sampah.',
                'data' => null,
            ], 422);
        }
        $pembayaran = $pengumpulan->pembayaran()->first();
        if ($pembayaran && $pembayaran->status === 'Lunas') {
            return response()->json([
                'status' => false,
                'message' => 'Pembayaran sudah lunas.',
                'data' => null,
            ], 422);
        }

        $totalBiaya = round((float) $pengumpulan->detailPengumpulanSampah->sum('nilai'), 2);
        $pembayaran = DB::transaction(function () use ($pengumpulan, $pembayaran, $totalBiaya, $request) {
            if (!$pembayaran) {
                $pembayaran = Pembayaran::create([
                    'id_pengumpulan_sampah' => $pengumpulan->id_pengumpulan_sampah,
                    'id_admin' => $request->user()->id_admin,
                    'jumlah_dibayar' => 0,
                    'tanggal_bayar' => null,
                    'status' => 'Menunggu Pembayaran',
                ]);
                $this->catatLog(
                    'create',
                    'pembayaran',
                    $pembayaran->id_pembayaran,
                    null,
                    $pembayaran->toArray(),
                    'Membuat catatan pembayaran sebelum menandai lunas.'
                );
            }
            $dataLama = $pembayaran->toArray();
            $pembayaran->update([
                'id_admin' => $request->user()->id_admin,
                'jumlah_dibayar' => $totalBiaya,
                'tanggal_bayar' => now()->toDateString(),
                'status' => 'Lunas',
            ]);
            $this->catatLog(
                'update',
                'pembayaran',
                $pembayaran->id_pembayaran,
                $dataLama,
                $pembayaran->fresh()->toArray(),
                'Pembayaran berhasil ditandai lunas.'
            );

            return $pembayaran->fresh();
        });
        return response()->json([
            'status' => true,
            'message' => 'Pembayaran berhasil ditandai lunas.',
            'data' => $pembayaran,
        ]);
    }

    public function nota($id)
    {
        $pengumpulan = PengumpulanSampah::with([
            'warga',
            'detailPengumpulanSampah.jenisSampah',
            'pembayaran',
        ])->find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if ($pengumpulan->detailPengumpulanSampah->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Nota belum tersedia karena belum ada detail sampah.',
                'data' => null,
            ], 422);
        }
        $pembayaran = $pengumpulan->pembayaran;
        $totalBiaya = round((float) $pengumpulan->detailPengumpulanSampah->sum('nilai'), 2);
        $totalBerat = round((float) $pengumpulan->detailPengumpulanSampah->sum('berat'), 2);
        $totalJenis = $pengumpulan->detailPengumpulanSampah->count();
        $statusUi = $pembayaran?->status === 'Lunas'? 'Lunas': 'Menunggu Pembayaran';
        return response()->json([
            'status' => true,
            'message' => 'Nota pengumpulan sampah berhasil diambil.',
            'data' => [
                'id_pengumpulan_sampah' => $pengumpulan->id_pengumpulan_sampah,
                'tanggal' => $pengumpulan->tanggal,
                'warga' => $pengumpulan->warga,
                'detail_sampah' => $pengumpulan->detailPengumpulanSampah,
                'total_biaya' => $totalBiaya,
                'total_berat'=> $totalBerat,
                'total_jenis'=> $totalJenis,
                'status_pengumpulan' => $pengumpulan->status,
                'status_ui' => $statusUi,
                'pembayaran' => $pembayaran,
                'status_pembayaran' => $pembayaran?->status,
            ],
        ]);
    }

    public function indexPembayaran(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:Menunggu Pembayaran,Lunas',
            'tanggal_bayar' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $limit = (int) $request->input('limit', 10);
        $query = Pembayaran::with([
            'pengumpulanSampah.warga',
            'pengumpulanSampah.detailPengumpulanSampah.jenisSampah',
        ]);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tanggal_bayar')) {
            $query->whereDate('tanggal_bayar', $request->tanggal_bayar);
        }
        $pembayaran = $query
            ->orderByDesc('tanggal_bayar')
            ->orderByDesc('id_pembayaran')
            ->paginate($limit);
        $pembayaran->getCollection()->transform(
            function ($item) {
                $pengumpulan = $item->pengumpulanSampah;
                $item->total_biaya = $pengumpulan? (float) $pengumpulan->detailPengumpulanSampah->sum('nilai'): 0;
                $item->status_pembayaran = $item->status;
                return $item;
            }
        );

        return response()->json([
            'status' => true,
            'message' => 'Daftar pembayaran berhasil diambil.',
            'data' => $pembayaran->items(),
            'meta' => [
                'page' => $pembayaran->currentPage(),
                'limit' => $pembayaran->perPage(),
                'total_data' => $pembayaran->total(),
                'total_page' => $pembayaran->lastPage(),
            ],
        ]);
    }

    public function destroy($id)
    {
        $pengumpulan = PengumpulanSampah::with(['detailPengumpulanSampah', 'pembayaran'
        ])->find($id);
        if (!$pengumpulan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak ditemukan.'
            ], 404);
        }
        $statusPembayaran = $pengumpulan->pembayaran?->status;
        if (
            $pengumpulan->status !== 'Menunggu Dipilah' && $statusPembayaran !== 'Lunas'
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengumpulan sampah tidak dapat dihapus karena belum lunas.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            if ($statusPembayaran === 'Lunas') {
                $pengumpulan->detailPengumpulanSampah()->delete();
                $pengumpulan->pembayaran()->delete();
            }
            $pengumpulan->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data pengumpulan sampah berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data pengumpulan sampah.',
            ], 500);
        }
    }
}