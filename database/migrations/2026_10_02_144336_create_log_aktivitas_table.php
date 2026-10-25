<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id('id_log_aktivitas');
            $table->unsignedBigInteger('id_admin');
            $table->enum('aksi', ['create', 'read', 'update', 'delete']);
            $table->enum('entitas', [
                'admin',
                'jenis_sampah',
                'jenis_olahan',
                'komposisi_olahan',
                'pengumpulan_sampah',
                'detail_pengumpulan_sampah',
                'pembayaran',
                'pengolahan_sampah',
                'detail_pengolahan_sampah',
                'hasil_olahan',
                'status_operasional',
                'warga',
                'pic_pengolah'
            ]);
            $table->unsignedBigInteger('id_entitas');
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_admin')
                ->references('id_admin')
                ->on('admin')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
