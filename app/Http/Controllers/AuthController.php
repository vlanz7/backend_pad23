<?php
    
namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|in:web,android',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data login tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $admin = Admin::where('username', $request->username)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah.',
                'data' => (object) [],
            ], 401);
        }
        if ($admin->status !== 'Aktif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sudah nonaktif.',
                'data' => (object) [],
            ], 403);
        }

        $expiresAt = $request->device_name === 'web'
            ? now()->addHours(8)
            : now()->addDays(30);
        $token = $admin->createToken(
            $request->device_name,
            ['*'],
            $expiresAt
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt,
                'admin' => [
                    'id_admin' => $admin->id_admin,
                    'nama' => $admin->nama,
                    'username' => $admin->username,
                    'role' => $admin->role,
                ],
            ],
        ], 200);
    }
    
    public function me(Request $request)
    {
        $admin = $request->user();
        return response()->json([
            'status' => 'success',
            'message' => 'Data pengguna berhasil diambil.',
            'data' => [
                'id_admin' => $admin->id_admin,
                'nama' => $admin->nama,
                'username' => $admin->username,
                'role' => $admin->role,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
            'data' => (object) [],
        ], 200);
    }

    public function ubahPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:8',
            'konfirmasi_password_baru' => 'required|string|same:password_baru',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data ubah password tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = $request->user();
        if (!Hash::check($request->password_lama, $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama tidak sesuai.',
                'data' => (object) [],
            ], 422);
        }
        $admin->password = Hash::make($request->password_baru);
        $admin->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui.',
            'data' => (object) [],
        ], 200);
    }
}
