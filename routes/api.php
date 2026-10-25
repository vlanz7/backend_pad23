<?php

use App\Http\Controllers\JenisOlahanController;
use App\Http\Controllers\JenisSampahController;
use App\Http\Controllers\PicPengolahController;
use App\Http\Controllers\StatusOperasionalController;
use App\Http\Controllers\WargaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PengumpulanSampahController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Pengaturan Jenis Sampah
    Route::get('/jenis-sampah', [JenisSampahController::class, 'index']);
    Route::post('/jenis-sampah', [JenisSampahController::class, 'store']);
    Route::put('/jenis-sampah/{id}', [JenisSampahController::class, 'update']);
    Route::delete('/jenis-sampah/{id}', [JenisSampahController::class, 'destroy']);
    Route::get('/jenis-sampah/  {id}/riwayat-tarif', [JenisSampahController::class, 'riwayatTarif']);

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
    Route::get('/admin', [AdminController::class, 'index']);
    Route::post('/admin', [AdminController::class, 'store']);
    Route::delete('/admin/{id}', [AdminController::class, 'destroy']);

    // Modul Setor/Pengumpulan Sampah
    Route::get('/pengumpulan-sampah', [PengumpulanSampahController::class, 'index']);
    Route::post('/pengumpulan-sampah', [PengumpulanSampahController::class, 'store']);
    Route::post('/pengumpulan-sampah/{id}/detail', [PengumpulanSampahController::class, 'storeDetail']);
    Route::delete('/pengumpulan-sampah/{id}/detail/{id_detail}', [PengumpulanSampahController::class, 'destroyDetail']);
    Route::patch('/pengumpulan-sampah/{id}/pembayaran', [PengumpulanSampahController::class, 'storePembayaran']);
    Route::get('/pengumpulan-sampah/{id}/nota', [PengumpulanSampahController::class, 'nota']);
    Route::get('/pengumpulan-sampah/{id}', [PengumpulanSampahController::class, 'show']);
    Route::get('/pembayaran', [PengumpulanSampahController::class, 'indexPembayaran']);
});