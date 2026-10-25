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
        Schema::create('detail_pengumpulan_sampah', function (Blueprint $table) {
            $table->id('id_detail_pengumpulan_sampah');
            $table->unsignedBigInteger('id_pengumpulan_sampah');
            $table->unsignedBigInteger('id_jenis_sampah');
            $table->decimal('berat', 10, 2);
            $table->decimal('nilai', 15, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_pengumpulan_sampah')
                ->references('id_pengumpulan_sampah')
                ->on('pengumpulan_sampah')
                ->cascadeOnDelete();

            $table->foreign('id_jenis_sampah')
                ->references('id_jenis_sampah')
                ->on('jenis_sampah')
                ->restrictOnDelete();
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pengumpulan_sampah');
    }
};
