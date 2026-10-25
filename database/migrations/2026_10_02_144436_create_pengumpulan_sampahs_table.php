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
        Schema::create('pengumpulan_sampah', function (Blueprint $table) {
            $table->id('id_pengumpulan_sampah');
            $table->unsignedBigInteger('id_warga');
            $table->unsignedBigInteger('id_admin');
            $table->enum('status', ['Menunggu Dipilah', 'Sedang Dipilah', 'Selesai Dipilah']);
            $table->date('tanggal');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_warga')
                ->references('id_warga')
                ->on('warga')
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
        Schema::dropIfExists('pengumpulan_sampah');
    }
};
