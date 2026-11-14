<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\School;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolId = School::query()->orderBy('id')->value('id');

        if ($schoolId === null) {
            return;
        }

        $types = [
            [
                'code' => 'UTS',
                'name' => 'Ujian Tengah Semester',
                'description' => 'Penilaian tengah semester untuk mengukur pemahaman materi setengah tahun.',
                'is_active' => true,
            ],
            [
                'code' => 'UAS',
                'name' => 'Ujian Akhir Semester',
                'description' => 'Penilaian akhir semester sebagai evaluasi menyeluruh.',
                'is_active' => false,
            ],
            [
                'code' => 'PTK',
                'name' => 'Penilaian Tengah Kelas',
                'description' => 'Penilaian formatif di tengah waktu belajar kelas.',
                'is_active' => false,
            ],
            [
                'code' => 'PAK',
                'name' => 'Penilaian Akhir Kelas',
                'description' => 'Penilaian akhir kegiatan pembelajaran di kelas.',
                'is_active' => false,
            ],
            [
                'code' => 'UN',
                'name' => 'Ujian Nasional',
                'description' => 'Penilain Nasional untuk menyelaraskan standar kompetensi lulusan.',
                'is_active' => false,
            ],
            [
                'code' => 'ANBK',
                'name' => 'Asesmen Nasional Berbasis Komputer',
                'description' => 'Asesmen nasional yang diselenggarakan secara berbasis komputer.',
                'is_active' => false,
            ],
            [
                'code' => 'TPS',
                'name' => 'Tes Potensi Sekolah',
                'description' => 'Tes untuk mengukur potensi akademik peserta didik.',
                'is_active' => false,
            ],
            [
                'code' => 'UKK',
                'name' => 'Ujian Keterampilan Keahlian',
                'description' => 'Ujian keterampilan keahlian jurusan.',
                'is_active' => false,
            ],
        ];

        foreach ($types as $type) {
            ExamType::firstOrNew([
                'school_id' => $schoolId,
                'code' => $type['code'],
            ])
                ->forceFill([...$type, 'school_id' => $schoolId])
                ->save();
        }
    }
}
