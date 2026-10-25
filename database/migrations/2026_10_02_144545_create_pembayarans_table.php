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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->unsignedBigInteger('id_pengumpulan_sampah');
            $table->unsignedBigInteger('id_admin');
            $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            $table->date('tanggal_bayar')->nullable();
            $table->enum('status', ['Menunggu Pembayaran', 'Lunas']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_pengumpulan_sampah')
                ->references('id_pengumpulan_sampah')
                ->on('pengumpulan_sampah')
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
        Schema::dropIfExists('pembayaran');
    }
};
