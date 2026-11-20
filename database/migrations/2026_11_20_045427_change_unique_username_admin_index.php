<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropUnique('admin_username_unique');
        });
    }

    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->unique('username', 'admin_username_unique');
        });
    }
};
