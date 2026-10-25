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
        Schema::create('jenis_olahan', function (Blueprint $table) {
            $table->id('id_jenis_olahan');
            $table->unsignedBigInteger('updated_by');
            $table->string('nama_olahan');
            $table->decimal('tarif_nilai_olahan', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->string('satuan_massa');
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
        Schema::dropIfExists('jenis_olahan');
    }
};
