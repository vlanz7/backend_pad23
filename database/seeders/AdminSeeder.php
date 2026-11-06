<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'nama' => 'Super Admin',
            'username' => 'superadmin',
            'password' => Hash::make('akusuperadmin123'),
            'role' => 'Super Admin',
            'status' => 'Aktif',
        ]);
    }
}
