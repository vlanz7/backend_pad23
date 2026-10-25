<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
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
                'message' => 'Parameter tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $limit = min((int) $request->query('limit', 10), 10);
        $page = (int) $request->query('page', 1);
        $query = Admin::query()->where('role', 'Admin');
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {$q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $admins = $query->orderBy('id_admin', 'asc')->paginate($limit, ['*'], 'page', $page);
        $data = collect($admins->items())->map(function ($admin) {
            return [
                'id_admin' => $admin->id_admin,
                'nama' => $admin->nama,
                'username' => $admin->username,
                'role' => $admin->role,
                'status' => $admin->status,
                'created_at' => $admin->created_at,
            ];
        })->values();
        return response()->json([
            'status' => 'success',
            'message' => 'Daftar admin berhasil diambil.',
            'data' => $data,
            'meta' => [
                'page' => $admins->currentPage(),
                'limit' => $admins->perPage(),
                'total_data' => $admins->total(),
                'total_page' => $admins->lastPage(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:admin,username',
            'password' => 'required|string|min:8',
            'konfirmasi_password' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data admin tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = DB::transaction(function () use ($request) {
            $admin = Admin::create([
                'nama' => $request->nama,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role' => 'Admin',
                'status' => 'Aktif',
            ]);

            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'create',
                'entitas' => 'admin',
                'id_entitas' => $admin->id_admin,
                'data_lama' => null,
                'data_baru' => json_encode([
                    'id_admin' => $admin->id_admin,
                    'nama' => $admin->nama,
                    'username' => $admin->username,
                    'role' => $admin->role,
                    'status' => $admin->status,
                ], JSON_UNESCAPED_UNICODE),
                'keterangan' => 'Menambahkan admin baru.',
            ]);
            return $admin;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Admin berhasil ditambahkan.',
            'data' => [
                'id_admin' => $admin->id_admin,
                'nama' => $admin->nama,
                'username' => $admin->username,
                'role' => $admin->role,
                'status' => $admin->status,
                'created_at' => $admin->created_at,
            ],
        ], 201);
    }

    public function destroy($id)
    {
        $admin = Admin::where('role', 'Admin')->where('id_admin', $id)->first();
        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin tidak ditemukan.',
                'data' => (object) [],
            ], 404);
        }
        if ($admin->status === 'Nonaktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin sudah berstatus Nonaktif.',
                'data' => (object) [],
            ], 422);
        }
        DB::transaction(function () use ($admin) {
            $dataLama = [
                'id_admin' => $admin->id_admin,
                'nama' => $admin->nama,
                'username' => $admin->username,
                'role' => $admin->role,
                'status' => $admin->status,
            ];
            $admin->update([
                'status' => 'Nonaktif',
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // ID pelaku sementara, sesuaikan dengan pola controller sebelumnya
                'aksi' => 'update',
                'entitas' => 'admin',
                'id_entitas' => $admin->id_admin,
                'data_lama' => json_encode(
                    $dataLama,
                    JSON_UNESCAPED_UNICODE
                ),
                'data_baru' => json_encode([
                    'id_admin' => $admin->id_admin,
                    'nama' => $admin->nama,
                    'username' => $admin->username,
                    'role' => $admin->role,
                    'status' => $admin->status,
                ], JSON_UNESCAPED_UNICODE),
                'keterangan' => 'Menonaktifkan admin.',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Admin berhasil dinonaktifkan.',
            'data' => (object) [],
        ], 200);
    }
}