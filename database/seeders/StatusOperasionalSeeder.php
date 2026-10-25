<?php

namespace Database\Seeders;

use App\Models\StatusOperasional;
use Illuminate\Database\Seeder;

class StatusOperasionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StatusOperasional::create([
            'status' => 'buka', 'updated_by' => 1,
        ]);
    }
}
