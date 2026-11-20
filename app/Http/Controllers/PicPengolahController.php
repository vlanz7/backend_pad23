<?php

namespace App\Http\Controllers;

use App\Models\PicPengolah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PicPengolahController extends Controller
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
            'nomor_wa' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $sudahAda = PicPengolah::where('nomor_wa', $value)
                        ->where('status', 'Aktif')
                        ->exists();

                    if ($sudahAda) {
                        $fail('Nomor WhatsApp sudah digunakan oleh PIC pengolah yang masih aktif.');
                    }
                },
            ],
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
                'status' => 'Aktif',
                'updated_by' => $request->user()->id_admin,
            ]);
            $this->catatLog(
                'create',
                'pic_pengolah',
                $picPengolah->id_pic,
                null,
                [
                    'nama' => $picPengolah->nama,
                    'nomor_wa' => $picPengolah->nomor_wa,
                    'status' => $picPengolah->status,
                ],
                'Menambahkan data pic pengolah.'
            );
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
        DB::transaction(function () use ($picPengolah, $dataLama, $dataBaru, $request) { 
            $picPengolah->update([ 
                'updated_by' => $request->user()->id_admin,
                'nama' => $dataBaru['nama'],
                'nomor_wa' => $dataBaru['nomor_wa'], 
            ]); 
            if ($dataLama != $dataBaru) {
                $this->catatLog(
                    'update',
                    'pic_pengolah',
                    $picPengolah->id_pic,
                    $dataLama,
                    $dataBaru,
                    'Mengubah data pic pengolah.'
                );
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
                'updated_by' => Auth::user()->id_admin,
            ]);
            $this->catatLog(
                'delete',
                'pic_pengolah',
                $picPengolah->id_pic,
                $dataLama,
                [
                    'nama' => $picPengolah->nama,
                    'nomor_wa' => $picPengolah->nomor_wa,
                    'status' => $picPengolah->status,
                ],
                'Menonaktifkan data pic pengolah.'
            );
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Pic pengolah berhasil dinonaktifkan.',
            'data' => (object) [],
        ], 200);
    }
}