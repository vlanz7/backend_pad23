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
        Schema::create('komposisi_olahan', function (Blueprint $table) {
            $table->id('id_komposisi');
            $table->unsignedBigInteger('id_jenis_sampah');
            $table->unsignedBigInteger('id_jenis_olahan');

            $table->foreign('id_jenis_sampah')
                ->references('id_jenis_sampah')
                ->on('jenis_sampah')
                ->restrictOnDelete();

            $table->foreign('id_jenis_olahan')
                ->references('id_jenis_olahan')
                ->on('jenis_olahan')
                ->restrictOnDelete();

            $table->unique(['id_jenis_sampah', 'id_jenis_olahan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komposisi_olahan');
    }
};
