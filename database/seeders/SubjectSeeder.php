<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'code' => 'MAT',
                'name' => 'Matematika',
                'is_active' => true,
            ],
            [
                'code' => 'BIN',
                'name' => 'Bahasa Indonesia',
                'is_active' => true,
            ],
            [
                'code' => 'BIG',
                'name' => 'Bahasa Inggris',
                'is_active' => true,
            ],
            [
                'code' => 'FIS',
                'name' => 'Fisika',
                'is_active' => false,
            ],
            [
                'code' => 'KIM',
                'name' => 'Kimia',
                'is_active' => false,
            ],
            [
                'code' => 'BIO',
                'name' => 'Biologi',
                'is_active' => false,
            ],
            [
                'code' => 'EKO',
                'name' => 'Ekonomi',
                'is_active' => false,
            ],
            [
                'code' => 'GEO',
                'name' => 'Geografi',
                'is_active' => false,
            ],
            [
                'code' => 'SEJ',
                'name' => 'Sejarah',
                'is_active' => false,
            ],
            [
                'code' => 'PAI',
                'name' => 'Pendidikan Agama Islam',
                'is_active' => false,
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrNew(['code' => $subject['code']])
                ->forceFill($subject)
                ->save();
        }
    }
}
