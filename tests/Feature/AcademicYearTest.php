<?php

namespace Tests\Feature;

use App\Enums\AcademicYearSemester;
use App\Models\AcademicYear;
use Database\Seeders\AcademicYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_is_generated_from_name_and_semester(): void
    {
        $ganjil = AcademicYear::factory()->ganjil()->create(['name' => '2024/2025']);
        $genap = AcademicYear::factory()->genap()->create(['name' => '2024/2025']);

        $this->assertSame('20241', $ganjil->code);
        $this->assertSame('20242', $genap->code);
    }

    public function test_code_is_regenerated_when_semester_changes(): void
    {
        $academicYear = AcademicYear::factory()->ganjil()->create(['name' => '2025/2026']);

        $academicYear->update(['semester' => AcademicYearSemester::Genap]);

        $this->assertSame('20252', $academicYear->fresh()->code);
    }

    public function test_only_one_academic_year_can_be_active(): void
    {
        $previous = AcademicYear::factory()->ganjil()->active()->create(['name' => '2023/2024']);
        $current = AcademicYear::factory()->ganjil()->active()->create(['name' => '2024/2025']);

        $this->assertFalse($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
    }

    public function test_semester_is_cast_to_enum(): void
    {
        $academicYear = AcademicYear::factory()->genap()->create(['name' => '2022/2023']);

        $this->assertInstanceOf(AcademicYearSemester::class, $academicYear->semester);
        $this->assertSame(AcademicYearSemester::Genap, $academicYear->semester);
    }

    public function test_invalid_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AcademicYear::factory()->ganjil()->create(['name' => '2024-2025']);
    }

    public function test_seeder_creates_both_semesters_with_single_active_year(): void
    {
        $this->seed(AcademicYearSeeder::class);

        $this->assertDatabaseCount('academic_years', 16);
        $this->assertDatabaseHas('academic_years', [
            'code' => '20241',
            'name' => '2024/2025',
            'semester' => AcademicYearSemester::Ganjil->value,
            'is_active' => true,
        ]);
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
    }
}
