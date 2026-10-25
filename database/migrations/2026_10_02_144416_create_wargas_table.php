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
        Schema::create('warga', function (Blueprint $table) {
            $table->id('id_warga');
            $table->unsignedBigInteger('updated_by');
            $table->string('nama');
            $table->string('nomor_wa');
            $table->enum('status', ['Aktif', 'Nonaktif']);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

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
        Schema::dropIfExists('warga');
    }
};
