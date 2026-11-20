<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WargaController extends Controller
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
        $validator = Validator::make($request->query(), [
            'search' => 'nullable|string',
            'status' => 'nullable|in:Aktif,Nonaktif',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $limit = min((int) $request->query('limit', 10), 10);
        $page = (int) $request->query('page', 1);
        $query = Warga::query();
        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->query('search') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $warga = $query->orderBy('id_warga', 'asc')->paginate($limit, ['*'], 'page', $page);
        $warga->getCollection()->transform(function ($item) {
            return $item->makeHidden(['updated_by']);
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Daftar warga berhasil diambil.',
            'data' => $warga->items(),
            'meta' => [
                'page' => $warga->currentPage(),
                'limit' => $warga->perPage(),
                'total_data' => $warga->total(),
                'total_page' => $warga->lastPage(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $warga = DB::transaction(function () use ($request) {
            $warga = Warga::create([
                'nama' => $request->nama,
                'nomor_wa' => $request->nomor_wa,
                'status' => 'aktif',
                'updated_by' => $request->user()->id_admin,
            ]);
            $this->catatLog(
                'create',
                'warga',
                $warga->id_warga,
                null,
                [
                    'nama' => $warga->nama,
                    'nomor_wa' => $warga->nomor_wa,
                    'status' => $warga->status,
                ],
                'Menambahkan data warga.'
            );
            return $warga;
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Warga berhasil ditambahkan.',
            'data' => $warga->makeHidden(['updated_by']),
        ], 201);
    }

    public function show($id)
    {
        $warga = Warga::find($id);
        if (!$warga) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data warga tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Detail warga berhasil diambil.',
            'data' => $warga->makeHidden(['updated_by']),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $warga = Warga::find($id);
        if (!$warga) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data warga tidak ditemukan.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }
        if ($warga->status === 'Nonaktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Warga yang sudah nonaktif tidak dapat diubah.',
            ], 422);
        }

        $dataLama = ['nama' => $warga->nama, 'nomor_wa' => $warga->nomor_wa,];
        $dataBaru = ['nama' => $request->nama, 'nomor_wa' => $request->nomor_wa,]; 
        DB::transaction(function () use ($warga, $dataLama, $dataBaru, $request) { 
            $warga->update([ 
                'updated_by' => $request->user()->id_admin, 
                'nama' => $dataBaru['nama'],
                'nomor_wa' => $dataBaru['nomor_wa'], 
            ]); 
            if ($dataLama != $dataBaru) {
                $this->catatLog(
                    'update',
                    'warga',
                    $warga->id_warga,
                    $dataLama,
                    $dataBaru,
                    'Mengubah data warga.'
                );
            } 
        }); 
        $warga->refresh(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Data Warga berhasil diperbarui.', 
            'data' => $warga->except(['updated_by']) 
        ], 200); 
    }

    public function destroy($id)
    {
        $warga = Warga::find($id);
        if (!$warga) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data warga tidak ditemukan.',
            ], 404);
        }
        if ($warga->status === 'Nonaktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Warga sudah berstatus nonaktif.',
            ], 422);
        }
        DB::transaction(function () use ($warga) {
            $dataLama = [
                'nama' => $warga->nama,
                'nomor_wa' => $warga->nomor_wa,
                'status' => $warga->status,
            ];
            $warga->update([
                'status' => 'Nonaktif',
                'updated_by' => Auth::user()->id_admin,
            ]);
            $this->catatLog(
                'delete',
                'warga',
                $warga->id_warga,
                $dataLama,
                [
                    'nama' => $warga->nama,
                    'nomor_wa' => $warga->nomor_wa,
                    'status' => $warga->status,
                ],
                'Menonaktifkan data warga.'
            );
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Warga berhasil dinonaktifkan.',
            'data' => (object) [],
        ], 200);
    }
}