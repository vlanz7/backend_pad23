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
        Schema::create('detail_pengolahan_sampah', function (Blueprint $table) {
            $table->id('id_detail_pengolahan_sampah');
            $table->unsignedBigInteger('id_komposisi');
            $table->unsignedBigInteger('id_olah_sampah');
            $table->decimal('berat_digunakan', 10, 2);

            $table->foreign('id_komposisi')
                ->references('id_komposisi')
                ->on('komposisi_olahan')
                ->restrictOnDelete();

            $table->foreign('id_olah_sampah')
                ->references('id_olah_sampah')
                ->on('pengolahan_sampah')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pengolahan_sampah');
    }
};
