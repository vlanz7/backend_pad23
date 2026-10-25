<?php

namespace App\Http\Controllers;

use App\Models\PicPengolah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PicPengolahController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'search' => 'nullable|string',
            'status' => 'nullable|in:aktif,nonaktif',
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
        $query = PicPengolah::query();
        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->query('search') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $picPengolah = $query->orderBy('id_pic', 'asc')->paginate($limit, ['*'], 'page', $page);
        $picPengolah->getCollection()->transform(function ($item) {
            return $item->makeHidden(['updated_by']);
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pic pengolah berhasil diambil.',
            'data' => $picPengolah->items(),
            'meta' => [
                'page' => $picPengolah->currentPage(),
                'limit' => $picPengolah->perPage(),
                'total_data' => $picPengolah->total(),
                'total_page' => $picPengolah->lastPage(),
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
        $picPengolah = DB::transaction(function () use ($request) {
            $picPengolah = PicPengolah::create([
                'nama' => $request->nama,
                'nomor_wa' => $request->nomor_wa,
                'status' => 'aktif',
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'create',
                'entitas' => 'pic_pengolah',
                'id_entitas' => $picPengolah->id_pic,
                'data_lama' => null,
                'data_baru' => json_encode([
                    'nama' => $picPengolah->nama,
                    'nomor_wa' => $picPengolah->nomor_wa,
                    'status' => $picPengolah->status,
                ]),
                'keterangan' => 'Menambahkan data pic pengolah.',
            ]);
            return $picPengolah;
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Pic pengolah berhasil ditambahkan.',
            'data' => $picPengolah->makeHidden(['updated_by']),
        ], 201);
    }

    public function show($id)
    {
        $picPengolah = PicPengolah::find($id);
        if (!$picPengolah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pic pengolah tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Detail pic pengolah berhasil diambil.',
            'data' => $picPengolah->makeHidden(['updated_by']),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $picPengolah = picPengolah::find($id);
        if (!$picPengolah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pic pengolah tidak ditemukan.',
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
        if ($picPengolah->status === 'Nonaktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pic pengolah yang sudah nonaktif tidak dapat diubah.',
            ], 422);
        }

        $dataLama = ['nama' => $picPengolah->nama, 'nomor_wa' => $picPengolah->nomor_wa,];
        $dataBaru = ['nama' => $request->nama, 'nomor_wa' => $request->nomor_wa,]; 
        DB::transaction(function () use ($picPengolah, $dataLama, $dataBaru) { 
            $picPengolah->update([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama' => $dataBaru['nama'],
                'nomor_wa' => $dataBaru['nomor_wa'], 
            ]); 
            if ($dataLama != $dataBaru) { 
                LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'update',
                'entitas' => 'pic_pengolah',
                'id_entitas' => $picPengolah->id_pic,
                'data_lama' => json_encode($dataLama),
                'data_baru' => json_encode($dataBaru),
                'keterangan' => 'Mengubah data pic pengolah.',
                ]);
            } 
        }); 
        $picPengolah->refresh(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Data pic pengolah berhasil diperbarui.', 
            'data' => $picPengolah->except(['updated_by']) 
        ], 200); 
    }

    public function destroy($id)
    {
        $picPengolah = PicPengolah::find($id);
        if (!$picPengolah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pic pengolah tidak ditemukan.',
            ], 404);
        }
        if ($picPengolah->status === 'Nonaktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pic pengolah sudah berstatus nonaktif.',
            ], 422);
        }
        DB::transaction(function () use ($picPengolah) {
            $dataLama = [
                'nama' => $picPengolah->nama,
                'nomor_wa' => $picPengolah->nomor_wa,
                'status' => $picPengolah->status,
            ];
            $picPengolah->update([
                'status' => 'Nonaktif',
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'delete',
                'entitas' => 'pic_pengolah',
                'id_entitas' => $picPengolah->id_pic,
                'data_lama' => json_encode($dataLama),
                'data_baru' => json_encode([
                    'nama' => $picPengolah->nama,
                    'nomor_wa' => $picPengolah->nomor_wa,
                    'status' => $picPengolah->status,
                ]),
                'keterangan' => 'Menonaktifkan data pic pengolah.',
            ]);
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Pic pengolah berhasil dinonaktifkan.',
            'data' => (object) [],
        ], 200);
    }
}