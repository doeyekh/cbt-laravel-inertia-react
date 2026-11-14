<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->first();

        if ($academicYear === null) {
            return;
        }

        $schoolClasses = [
            'YAS-001' => [
                ['code' => 'KLS-01', 'name' => 'X IPA 1'],
                ['code' => 'KLS-02', 'name' => 'X IPA 2'],
                ['code' => 'KLS-03', 'name' => 'XI IPA 1'],
                ['code' => 'KLS-04', 'name' => 'XII IPA 1'],
            ],
            'YAS-002' => [
                ['code' => 'KLS-01', 'name' => 'VII B'],
                ['code' => 'KLS-02', 'name' => 'VIII B'],
                ['code' => 'KLS-03', 'name' => 'IX B'],
            ],
        ];

        School::query()
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get()
            ->each(function (School $school) use ($academicYear, $schoolClasses): void {
                $definitions = $schoolClasses[$school->code] ?? [];

                foreach ($definitions as $definition) {
                    SchoolClass::firstOrNew([
                        'school_id' => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'code' => $definition['code'],
                    ])->forceFill([
                        'name' => $definition['name'],
                        'is_active' => true,
                    ])->save();
                }
            });
    }
}
