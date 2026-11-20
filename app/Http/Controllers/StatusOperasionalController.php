<?php

namespace App\Http\Controllers;

use App\Models\StatusOperasional;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StatusOperasionalController extends Controller
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
        DB::transaction(function () use ($statusOperasional, $statusLama, $statusBaru, $request) {
            if ($statusLama !== $statusBaru) {
                $statusOperasional->update([
                    'status' => $statusBaru,
                    'updated_by' => $request->user()->id_admin, 
                ]);
                $this->catatLog(
                    'update',
                    'status_operasional',
                    $statusOperasional->id_status_operasional,
                    ['status' => $statusLama],
                    ['status' => $statusBaru],
                    'Mengubah status operasional.'
                );
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