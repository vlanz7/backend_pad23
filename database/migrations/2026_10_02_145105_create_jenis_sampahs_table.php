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
        Schema::create('jenis_sampah', function (Blueprint $table) {
            $table->id('id_jenis_sampah');
            $table->unsignedBigInteger('updated_by');
            $table->string('nama_sampah');
            $table->string('satuan_massa');
            $table->decimal('tarif_jasa_pengelolaan', 15, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('updated_by')
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
        Schema::dropIfExists('jenis_sampah');
    }
};
