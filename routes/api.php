<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HasilOlahanController;
use App\Http\Controllers\JenisOlahanController;
use App\Http\Controllers\JenisSampahController;
use App\Http\Controllers\PengolahanSampahController;
use App\Http\Controllers\PengumpulanSampahController;
use App\Http\Controllers\PicPengolahController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\StatusOperasionalController;
use App\Http\Controllers\WargaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Login
    // Public Dashboard
    Route::get('/public-dashboard', [PublicDashboardController::class, 'index']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        // Admin Dashboard
        Route::get('/admin-dashboard', [AdminDashboardController::class, 'index']);
        
        // Authentication
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::patch('/auth/me/password', [AuthController::class, 'ubahPassword']);

        // Pengaturan Jenis Sampah
        Route::get('/jenis-sampah', [JenisSampahController::class, 'index']);
        Route::post('/jenis-sampah', [JenisSampahController::class, 'store']);
        Route::put('/jenis-sampah/{id}', [JenisSampahController::class, 'update']);
        Route::delete('/jenis-sampah/{id}', [JenisSampahController::class, 'destroy']);
        Route::get('/jenis-sampah/{id}/riwayat-tarif', [JenisSampahController::class, 'riwayatTarif']);

        // Pengaturan Jenis Olahan
        Route::get('/jenis-olahan', [JenisOlahanController::class, 'index']);
        Route::post('/jenis-olahan', [JenisOlahanController::class, 'store']);
        Route::put('/jenis-olahan/{id}', [JenisOlahanController::class, 'update']);
        Route::delete('/jenis-olahan/{id}', [JenisOlahanController::class, 'destroy']);
        Route::get('/jenis-olahan/{id}/komposisi', [JenisOlahanController::class, 'indexKomposisi']);
        Route::post('/jenis-olahan/{id}/komposisi', [JenisOlahanController::class, 'storeKomposisi']);
        Route::delete('/jenis-olahan/{id}/komposisi/{id_komposisi}', [JenisOlahanController::class, 'destroyKomposisi']);

        //Pengaturan Status Operasional
        Route::get('/status-operasional', [StatusOperasionalController::class, 'index']);
        Route::patch('/status-operasional', [StatusOperasionalController::class, 'update']);

        //Pengaturan Warga
        Route::get('/warga', [WargaController::class, 'index']);
        Route::post('/warga', [WargaController::class, 'store']);
        Route::put('/warga/{id}', [WargaController::class, 'update']);
        Route::delete('/warga/{id}', [WargaController::class, 'destroy']);
        Route::get('/warga/{id}', [WargaController::class, 'show']);
        
        //Pengaturan Pic Pengolah
        Route::get('/pic-pengolah', [PicPengolahController::class, 'index']);
        Route::post('/pic-pengolah', [PicPengolahController::class, 'store']);
        Route::put('/pic-pengolah/{id}', [PicPengolahController::class, 'update']);
        Route::delete('/pic-pengolah/{id}', [PicPengolahController::class, 'destroy']);
        Route::get('/pic-pengolah/{id}', [PicPengolahController::class, 'show']);
        
        //Pengaturan Admin
        Route::middleware('SuperAdmin')->group(function () {
            Route::get('/admin', [AdminController::class, 'index']);
            Route::post('/admin', [AdminController::class, 'store']);
            Route::delete('/admin/{id}', [AdminController::class, 'destroy']);
            Route::patch('/admin/{id}', [AdminController::class, 'ubahPassword']);
            Route::get('/audit-log', [AuditLogController::class, 'index']);
        });

        // Modul Setor/Pengumpulan Sampah
        Route::get('/pengumpulan-sampah', [PengumpulanSampahController::class, 'index']);
        Route::post('/pengumpulan-sampah', [PengumpulanSampahController::class, 'store']);
        Route::delete('/pengumpulan-sampah/{id}', [PengumpulanSampahController::class, 'destroy']);
        Route::post('/pengumpulan-sampah/{id}/detail', [PengumpulanSampahController::class, 'storeDetail']);
        Route::delete('/pengumpulan-sampah/{id}/detail/{id_detail}', [PengumpulanSampahController::class, 'destroyDetail']);
        Route::patch('/pengumpulan-sampah/{id}/pembayaran', [PengumpulanSampahController::class, 'storePembayaran']);
        Route::get('/pengumpulan-sampah/{id}/nota', [PengumpulanSampahController::class, 'nota']);
        Route::get('/pengumpulan-sampah/{id}', [PengumpulanSampahController::class, 'show']);
        Route::get('/pembayaran', [PengumpulanSampahController::class, 'indexPembayaran']);

        // Modul Pengolahan Sampah
        Route::get('/material-siap-olah', [PengolahanSampahController::class, 'materialSiapOlah']);
        Route::get('/pengolahan-sampah', [PengolahanSampahController::class, 'index']);
        Route::post('/pengolahan-sampah', [PengolahanSampahController::class, 'store']);
        Route::get('/pengolahan-sampah/{id}', [PengolahanSampahController::class, 'show']);
        Route::delete('/pengolahan-sampah/{id}', [PengolahanSampahController::class, 'destroy']);

        // Modul Pencatatan Hasil Olahan
        Route::post('/pengolahan-sampah/{id}/hasil-olahan', [HasilOlahanController::class, 'store']);
        Route::get('/hasil-olahan', [HasilOlahanController::class, 'index']);
        Route::get('/hasil-olahan/{id}', [HasilOlahanController::class, 'show']);
        Route::patch('/hasil-olahan/{id}/publikasikan', [HasilOlahanController::class, 'publikasikan']);
    });
});