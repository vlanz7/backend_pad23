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
        Schema::create('hasil_olahan', function (Blueprint $table) {
            $table->id('id_hasilolahan');
            $table->unsignedBigInteger('id_olah_sampah');
            $table->unsignedBigInteger('id_admin');
            $table->decimal('berat_hasil_olahan', 10, 2);
            $table->decimal('nilai_jual', 15, 2);
            $table->enum('status_publikasi', ['Belum Dipublikasi', 'Sudah Dipublikasi']);
            $table->date('tanggal_direkap');

            $table->foreign('id_olah_sampah')
                ->references('id_olah_sampah')
                ->on('pengolahan_sampah')
                ->restrictOnDelete();

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
        Schema::dropIfExists('hasil_olahan');
    }
};
