<?php

namespace App\Http\Controllers;

use App\Models\HasilOlahan;
use App\Models\PengolahanSampah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HasilOlahanController extends Controller
{
    private function catatLog(string $aksi, string $entitas, int $idEntitas, $dataLama = null,
    $dataBaru = null, ?string $keterangan = null): void{
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
            'status_publikasi' => 'nullable|in:Belum Dipublikasi,Sudah Dipublikasi',
            'tanggal_direkap' => 'nullable|date',
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
        $query = HasilOlahan::with(['pengolahanSampah']);
        if ($request->filled('status_publikasi')) {
            $query->where('status_publikasi', $request->status_publikasi);
        }
        if ($request->filled('tanggal_direkap')) {
            $query->whereDate('tanggal_direkap', $request->tanggal_direkap);
        }
        $hasilOlahan = $query
            ->orderByDesc('tanggal_direkap')
            ->orderByDesc('id_hasilolahan')
            ->paginate($limit);
        return response()->json([
            'status' => true,
            'message' => 'Daftar hasil olahan berhasil diambil.',
            'data' => $hasilOlahan->items(),
            'meta' => [
                'page' => $hasilOlahan->currentPage(),
                'limit' => $hasilOlahan->perPage(),
                'total_data' => $hasilOlahan->total(),
                'total_page' => $hasilOlahan->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'berat_hasil_olahan' => 'required|numeric|min:0.01',
            'nilai_jual' => 'required|numeric|min:0',
            'status_publikasi' => 'required|in:Belum Dipublikasi,Sudah Dipublikasi',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }
        $hasilOlahan = DB::transaction(function () use ($request, $id) {
            $pengolahan = PengolahanSampah::where(
                'id_olah_sampah',
                $id
            )->lockForUpdate()->first();
            if (!$pengolahan || $pengolahan->status !== 'Sedang Diolah') {
                abort(422, 'Pengolahan sampah tidak ditemukan atau sudah selesai diolah.');
            }
            if (HasilOlahan::where('id_olah_sampah', $pengolahan->id_olah_sampah)->exists()) {
                abort(422, 'Hasil olahan untuk pengolahan sampah ini sudah tercatat.');
            }
            $hasilOlahan = HasilOlahan::create([
                'id_olah_sampah' => $pengolahan->id_olah_sampah,
                'id_admin' => $request->user()->id_admin,
                'berat_hasil_olahan' => $request->berat_hasil_olahan,
                'nilai_jual' => $request->nilai_jual,
                'status_publikasi' => $request->status_publikasi,
                'tanggal_direkap' => now()->toDateString(),
            ]);
            $this->catatLog(
                'create',
                'hasil_olahan',
                $hasilOlahan->id_hasilolahan,
                null,
                $hasilOlahan->toArray(),
                'Mencatat hasil olahan sampah.'
            );
            $dataLama = $pengolahan->toArray();
            $pengolahan->update([
                'status' => 'Selesai Diolah',
                'tgl_selesai' => now(),
            ]);
            $this->catatLog(
                'update',
                'pengolahan_sampah',
                $pengolahan->id_olah_sampah,
                $dataLama,
                $pengolahan->fresh()->toArray(),
                'Mengubah status pengolahan menjadi selesai setelah hasil olahan dicatat.'
            );
            return $hasilOlahan;
        });
        return response()->json([
            'status' => true,
            'message' => 'Hasil olahan berhasil dicatat.',
            'data' => $hasilOlahan->load('pengolahanSampah'),
        ], 201);
    }

    public function publikasikan(Request $request, $id)
    {
        $hasilOlahan = HasilOlahan::find($id);
        if (!$hasilOlahan) {
            return response()->json([
                'status' => false,
                'message' => 'Data hasil olahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        if ($hasilOlahan->status_publikasi === 'Sudah Dipublikasi') {
            return response()->json([
                'status' => false,
                'message' => 'Hasil olahan sudah dipublikasi.',
                'data' => null,
            ], 422);
        }

        $hasilOlahan = DB::transaction(function () use ($hasilOlahan, $request) {
            $dataLama = $hasilOlahan->toArray();
            $hasilOlahan->update([
                'status_publikasi' => 'Sudah Dipublikasi',
                'id_admin' => $request->user()->id_admin,
            ]);
            $this->catatLog(
                'update',
                'hasil_olahan',
                $hasilOlahan->id_hasilolahan,
                $dataLama,
                $hasilOlahan->fresh()->toArray(),
                'Mempublikasikan hasil olahan sampah.'
            );
            return $hasilOlahan->fresh();
        });
        return response()->json([
            'status' => true,
            'message' => 'Hasil olahan berhasil dipublikasi.',
            'data' => $hasilOlahan->load('pengolahanSampah'),
        ]);
    }

    public function show($id)
    {
        $hasilOlahan = HasilOlahan::with([
            'pengolahanSampah.detailPengolahanSampah.komposisiOlahan.jenisSampah',
        ])->find($id);

        if (!$hasilOlahan) {
            return response()->json([
                'status' => false,
                'message' => 'Data hasil olahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Detail hasil olahan berhasil diambil.',
            'data' => $hasilOlahan,
        ]);
    }
}