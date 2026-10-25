<?php

namespace App\Http\Controllers;

use App\Models\StatusOperasional;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StatusOperasionalController extends Controller
{
    public function index()
    {
        $statusOperasional = StatusOperasional::first();
        if (!$statusOperasional) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status operasional belum tersedia.',
                'data' => null
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Status operasional berhasil diambil.',
            'data' => [
                'status' => $statusOperasional->status,
                'updated_at' => $statusOperasional->updated_at
            ]
        ], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:buka,tutup',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }
        $statusOperasional = StatusOperasional::first();
        if (!$statusOperasional) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status operasional belum tersedia.',
                'data' => null
            ], 404);
        }
        $statusLama = $statusOperasional->status;
        $statusBaru = $request->status;
        DB::transaction(function () use ($statusOperasional, $statusLama, $statusBaru) {
            if ($statusLama !== $statusBaru) {
                $statusOperasional->update([
                    'status' => $statusBaru,
                    'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                ]);
                LogAktivitas::create([
                    'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                    'aksi' => 'update',
                    'entitas' => 'status_operasional',
                    'id_entitas' => $statusOperasional->id_status_operasional,
                    'data_lama' => json_encode([
                        'status' => $statusLama
                    ]),
                    'data_baru' => json_encode([
                        'status' => $statusBaru
                    ]),
                    'keterangan' => 'Mengubah status operasional.'
                ]);
            }
        });
        $statusOperasional->refresh();
        return response()->json([
            'status' => 'success',
            'message' => 'Status operasional berhasil diperbarui.',
            'data' => [
                'status' => $statusOperasional->status,
                'updated_at' => $statusOperasional->updated_at
            ]
        ], 200);
    }
}