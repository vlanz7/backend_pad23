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
        Schema::create('pengolahan_sampah', function (Blueprint $table) {
            $table->id('id_olah_sampah');
            $table->unsignedBigInteger('id_jenis_olahan');
            $table->unsignedBigInteger('id_admin');
            $table->unsignedBigInteger('id_pic');
            $table->enum('status', ['Sedang Diolah', 'Selesai Diolah']);
            $table->dateTime('tgl_mulai');
            $table->dateTime('tgl_selesai')->nullable();

            $table->foreign('id_jenis_olahan')
                ->references('id_jenis_olahan')
                ->on('jenis_olahan')
                ->restrictOnDelete();

            $table->foreign('id_admin')
                ->references('id_admin')
                ->on('admin')
                ->restrictOnDelete();

            $table->foreign('id_pic')
                ->references('id_pic')
                ->on('pic_pengolah')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengolahan_sampah');
    }
};
