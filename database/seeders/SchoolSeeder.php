<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Seed the application's schools.
     */
    public function run(): void
    {
        School::factory()->active()->create([
            'name' => 'SMA Yayasan Nusantara',
            'code' => 'YAS-001',
            'address' => 'Jl. Pendidikan No. 1, Jakarta',
        ]);

        School::factory()->active()->create([
            'name' => 'SMP Yayasan Nusantara',
            'code' => 'YAS-002',
            'address' => 'Jl. Pendidikan No. 3, Jakarta',
        ]);
    }
}
