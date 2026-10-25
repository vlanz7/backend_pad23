<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WargaController extends Controller
{
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
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'create',
                'entitas' => 'warga',
                'id_entitas' => $warga->id_warga,
                'data_lama' => null,
                'data_baru' => json_encode([
                    'nama' => $warga->nama,
                    'nomor_wa' => $warga->nomor_wa,
                    'status' => $warga->status,
                ]),
                'keterangan' => 'Menambahkan data warga.',
            ]);
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
        DB::transaction(function () use ($warga, $dataLama, $dataBaru) { 
            $warga->update([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama' => $dataBaru['nama'],
                'nomor_wa' => $dataBaru['nomor_wa'], 
            ]); 
            if ($dataLama != $dataBaru) { 
                LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'update',
                'entitas' => 'warga',
                'id_entitas' => $warga->id_warga,
                'data_lama' => json_encode($dataLama),
                'data_baru' => json_encode($dataBaru),
                'keterangan' => 'Mengubah data warga.',
                ]);
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
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'delete',
                'entitas' => 'warga',
                'id_entitas' => $warga->id_warga,
                'data_lama' => json_encode($dataLama),
                'data_baru' => json_encode([
                    'nama' => $warga->nama,
                    'nomor_wa' => $warga->nomor_wa,
                    'status' => $warga->status,
                ]),
                'keterangan' => 'Menonaktifkan data warga.',
            ]);
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Warga berhasil dinonaktifkan.',
            'data' => (object) [],
        ], 200);
    }
}