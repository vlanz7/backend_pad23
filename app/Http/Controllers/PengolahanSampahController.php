<?php

namespace App\Http\Controllers;

use App\Models\PengolahanSampah;
use App\Models\DetailPengolahanSampah;
use App\Models\JenisSampah;
use App\Models\JenisOlahan;
use App\Models\KomposisiOlahan;
use App\Models\PicPengolah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengolahanSampahController extends Controller
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
            'status' => 'nullable|in:Sedang Diolah, Selesai Diolah',
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
        $query = PengolahanSampah::with(['jenisOlahan','picPengolah',]);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $batch = $query
            ->orderByDesc('tgl_mulai')
            ->orderByDesc('id_olah_sampah')
            ->paginate($limit);
        $batch->getCollection()->transform(function ($item) {
            $item->nama_olahan = $item->jenisOlahan->nama_olahan;
            $item->nama_pic = $item->picPengolah->nama;
            return $item;
        });
        return response()->json([
            'status' => true,
            'message' => 'Daftar batch pengolahan berhasil diambil.',
            'data' => $batch->items(),
            'meta' => [
                'page' => $batch->currentPage(),
                'limit' => $batch->perPage(),
                'total_data' => $batch->total(),
                'total_page' => $batch->lastPage(),
            ],
        ]);
    }

    public function materialSiapOlah()
    {
        $jenisSampah = JenisSampah::all();
        $data = $jenisSampah->map(function ($jenis) {
            // Menghitung berat sampah dari setoran yang sudah lunas
            $beratMasuk = DB::table('detail_pengumpulan_sampah')
                ->join('pengumpulan_sampah', 'detail_pengumpulan_sampah.id_pengumpulan_sampah', '=',
                    'pengumpulan_sampah.id_pengumpulan_sampah'
                )
                ->join('pembayaran', 'pengumpulan_sampah.id_pengumpulan_sampah', '=',
                    'pembayaran.id_pengumpulan_sampah'
                )
                ->where('detail_pengumpulan_sampah.id_jenis_sampah', $jenis->id_jenis_sampah)
                ->where('pembayaran.status', 'Lunas')
                ->sum('detail_pengumpulan_sampah.berat');
            // Menghitung berat yang sudah digunakan dalam seluruh batch
            $beratDipakai = DB::table('detail_pengolahan_sampah')
                ->join('komposisi_olahan', 'detail_pengolahan_sampah.id_komposisi', '=',
                    'komposisi_olahan.id_komposisi'
                )
                ->join('pengolahan_sampah', 'detail_pengolahan_sampah.id_olah_sampah', '=',
                    'pengolahan_sampah.id_olah_sampah'
                )
                ->where('komposisi_olahan.id_jenis_sampah', $jenis->id_jenis_sampah)
                ->whereIn('pengolahan_sampah.status', ['Sedang Diolah', 'Selesai Diolah',])
                ->sum('detail_pengolahan_sampah.berat_digunakan');
            $beratTersedia = max(0, (float) $beratMasuk - (float) $beratDipakai);
            return [
                'id_jenis_sampah' => $jenis->id_jenis_sampah,
                'nama_sampah' => $jenis->nama_sampah,
                'berat_tersedia' => round($beratTersedia, 2),
                'satuan_massa' => $jenis->satuan_massa,
            ];
        });
        return response()->json([
            'status' => true,
            'message' => 'Material siap olah berhasil diambil.',
            'data' => $data,
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_jenis_olahan' => 'required|integer',
            'id_pic' => 'required|integer',
            'bahan_baku' => 'required|array|min:1',
            'bahan_baku.*.id_komposisi' => 'required|integer|distinct',
            'bahan_baku.*.berat_digunakan' => 'required|numeric|gt:0',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }
        // Memastikan jenis olahan tersedia
        $jenisOlahan = JenisOlahan::find($request->id_jenis_olahan);
        if (!$jenisOlahan) {
            return response()->json([
                'status' => false,
                'message' => 'Jenis olahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        // Memastikan PIC tersedia dan aktif
        $pic = PicPengolah::find($request->id_pic);
        if (!$pic) {
            return response()->json([
                'status' => false,
                'message' => 'PIC pengolah tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        if ($pic->status !== 'Aktif') {
            return response()->json([
                'status' => false,
                'message' => 'PIC pengolah tidak aktif.',
                'data' => null,
            ], 422);
        }
        // Memastikan semua komposisi berasal dari jenis olahan yang dipilih
        $komposisiIds = collect($request->bahan_baku)
            ->pluck('id_komposisi');
        $komposisi = KomposisiOlahan::whereIn('id_komposisi',$komposisiIds)
            ->where('id_jenis_olahan', $request->id_jenis_olahan)
            ->get()
            ->keyBy('id_komposisi');
        $totalMasterKomposisi = KomposisiOlahan::where('id_jenis_olahan', $request->id_jenis_olahan)->count();
        if ($komposisi->count() !== $komposisiIds->unique()->count() || $komposisi->count() !== $totalMasterKomposisi) {
            return response()->json([
                'status' => false,
                'message' => 'Komposisi bahan baku tidak lengkap atau tidak sesuai dengan jenis olahan.',
                'data' => null,
            ], 422);
        }
        // Memastikan stok setiap bahan mencukupi
        foreach ($request->bahan_baku as $bahan) {
            $itemKomposisi = $komposisi->get($bahan['id_komposisi']);
            $jenisSampah = JenisSampah::find(
                $itemKomposisi->id_jenis_sampah
            );
            // Berat setoran yang sudah lunas
            $beratMasuk = DB::table('detail_pengumpulan_sampah')
                ->join('pengumpulan_sampah', 'detail_pengumpulan_sampah.id_pengumpulan_sampah',
                    '=', 'pengumpulan_sampah.id_pengumpulan_sampah'
                )
                ->join('pembayaran', 'pengumpulan_sampah.id_pengumpulan_sampah',
                    '=', 'pembayaran.id_pengumpulan_sampah'
                )
                ->where('detail_pengumpulan_sampah.id_jenis_sampah', $jenisSampah->id_jenis_sampah)
                ->where('pembayaran.status', 'Lunas')
                ->sum('detail_pengumpulan_sampah.berat');
            // Berat yang sudah digunakan batch lain
            $beratDipakai = DB::table('detail_pengolahan_sampah')
                ->join('komposisi_olahan', 'detail_pengolahan_sampah.id_komposisi',
                    '=', 'komposisi_olahan.id_komposisi'
                )
                ->join('pengolahan_sampah', 'detail_pengolahan_sampah.id_olah_sampah',
                    '=', 'pengolahan_sampah.id_olah_sampah'
                )
                ->where('komposisi_olahan.id_jenis_sampah', $jenisSampah->id_jenis_sampah)
                ->whereIn('pengolahan_sampah.status', ['Sedang Diolah', 'Selesai Diolah',])
                ->sum('detail_pengolahan_sampah.berat_digunakan');
            $beratTersedia = (float) $beratMasuk - (float) $beratDipakai;
            if ((float) $bahan['berat_digunakan'] > $beratTersedia) {
                return response()->json([
                    'status' => false,
                    'message' => 'Berat bahan baku melebihi stok yang tersedia.',
                    'data' => [
                        'nama_sampah' => $jenisSampah->nama_sampah,
                        'berat_tersedia' => round(max(0, $beratTersedia), 2),
                        'berat_diminta' => (float) $bahan['berat_digunakan'],
                    ],
                ], 422);
            }
        }
        // Menyimpan batch dan detail bahan baku
        $batch = DB::transaction(function () use ($request, $komposisi) {
            $batch = PengolahanSampah::create([
                'id_jenis_olahan' => $request->id_jenis_olahan,
                'id_admin' => $request->user()->id_admin,
                'id_pic' => $request->id_pic,
                'status' => 'Sedang Diolah',
                'tgl_mulai' => now(),
                'tgl_selesai' => null,
            ]);
            $this->catatLog(
                'create',
                'pengolahan_sampah',
                $batch->id_olah_sampah,
                null,
                $batch->toArray(),
                'Membuat batch pengolahan sampah baru.'
            );
            foreach ($request->bahan_baku as $bahan) {
                $detail = DetailPengolahanSampah::create([
                    'id_komposisi' => $bahan['id_komposisi'],
                    'id_olah_sampah' => $batch->id_olah_sampah,
                    'berat_digunakan' => round(
                        (float) $bahan['berat_digunakan'],
                        2
                    ),
                ]);

                $this->catatLog(
                    'create',
                    'detail_pengolahan_sampah',
                    $detail->id_detail_pengolahan_sampah,
                    null,
                    $detail->toArray(),
                    'Menambahkan bahan baku ke batch pengolahan.'
                );
            }
            return $batch;
        });
        return response()->json([
            'status' => true,
            'message' => 'Batch pengolahan berhasil dibuat.',
            'data' => $this->ambilDetailBatch($batch->id_olah_sampah),
        ], 201);
    }
    
    public function show($id)
    {
        $batch = PengolahanSampah::with(['jenisOlahan', 'picPengolah', 'detailPengolahanSampah.komposisiOlahan.jenisSampah',
        ])->find($id);
        if (!$batch) {
            return response()->json([
                'status' => false,
                'message' => 'Data batch pengolahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Detail batch pengolahan berhasil diambil.',
            'data' => $this->ambilDetailBatch($id),
        ]);
    }
    
    private function ambilDetailBatch($id)
    {
        $batch = PengolahanSampah::with(['jenisOlahan','picPengolah','detailPengolahanSampah.komposisiOlahan.jenisSampah',])
        ->findOrFail($id);
        return [
            'id_olah_sampah' => $batch->id_olah_sampah,
            'id_jenis_olahan' => $batch->id_jenis_olahan,
            'nama_olahan' => $batch->jenisOlahan->nama_olahan,
            'id_pic' => $batch->id_pic,
            'nama_pic' => $batch->picPengolah->nama,
            'id_admin' => $batch->id_admin,
            'status' => $batch->status,
            'tgl_mulai' => $batch->tgl_mulai,
            'tgl_selesai' => $batch->tgl_selesai,
            'bahan_baku' => $batch->detailPengolahanSampah->map(
                function ($detail) {
                    return [
                        'id_detail_pengolahan_sampah' => $detail->id_detail_pengolahan_sampah,
                        'id_komposisi' => $detail->id_komposisi,
                        'nama_sampah' => $detail->komposisiOlahan->jenisSampah->nama_sampah,
                        'berat_digunakan' => (float) $detail->berat_digunakan,
                    ];
                }
            )->values(),
        ];
    }

    public function destroy($id)
    {
        $batch = PengolahanSampah::with('detailPengolahanSampah')->find($id);
        if (!$batch) {
            return response()->json([
                'status' => false,
                'message' => 'Data batch pengolahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        if ($batch->status !== 'Sedang Diolah') {
            return response()->json([
                'status' => false,
                'message' => 'Batch yang sudah selesai diolah tidak dapat dihapus.',
                'data' => null,
            ], 422);
        }

        DB::transaction(function () use ($batch) {
            $dataLama = $batch->toArray();
            foreach ($batch->detailPengolahanSampah as $detail) {
                $this->catatLog(
                    'delete',
                    'detail_pengolahan_sampah',
                    $detail->id_detail_pengolahan_sampah,
                    $detail->toArray(),
                    null,
                    'Menghapus detail bahan baku batch pengolahan.'
                );
                $detail->delete();
            }
            $this->catatLog(
                'delete',
                'pengolahan_sampah',
                $batch->id_olah_sampah,
                $dataLama,
                null,
                'Menghapus batch pengolahan sampah.'
            );
            $batch->delete();
        });
        return response()->json([
            'status' => true,
            'message' => 'Batch pengolahan berhasil dihapus.',
            'data' => null,
        ]);
    }
}
