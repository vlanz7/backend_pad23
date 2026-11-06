<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_admin' => 'nullable|integer|exists:admin,id_admin',
            'entitas' => [
                'nullable',
                'string',
                'in:admin,jenis_sampah,jenis_olahan,komposisi_olahan,pengumpulan_sampah,detail_pengumpulan_sampah,pembayaran,pengolahan_sampah,detail_pengolahan_sampah,hasil_olahan,status_operasional,warga,pic_pengolah',
            ],
            'tanggal' => 'nullable|date_format:Y-m-d',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $limit = (int) $request->input('limit', 10);
        $page = (int) $request->input('page', 1);
        $query = LogAktivitas::with('admin:id_admin,nama');
        if ($request->filled('id_admin')) {
            $query->where('id_admin', $request->id_admin);
        }
        if ($request->filled('entitas')) {
            $query->where('entitas', $request->entitas);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }
        $logs = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id_log_aktivitas')
            ->paginate($limit, ['*'], 'page', $page);
        $data = $logs->getCollection()->map(function ($log) {
            return [
                'id_log' => $log->id_log_aktivitas,
                'id_admin' => $log->id_admin,
                'nama_admin' => $log->admin?->nama,
                'aksi' => $log->aksi,
                'entitas' => $log->entitas,
                'id_entitas' => $log->id_entitas,
                'data_lama' => $log->data_lama === null
                    ? null
                    : json_decode($log->data_lama),
                'data_baru' => $log->data_baru === null
                    ? null
                    : json_decode($log->data_baru),
                'keterangan' => $log->keterangan,
                'created_at' => $log->created_at,
            ];
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat aktivitas admin berhasil diambil.',
            'data' => $data,
            'meta' => [
                'page' => $logs->currentPage(),
                'limit' => $logs->perPage(),
                'total_data' => $logs->total(),
                'total_page' => $logs->lastPage(),
            ]
        ], 200);
    }
}