<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_belongs_to_school_and_academic_year(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->assertTrue($schoolClass->school->is($school));
        $this->assertTrue($schoolClass->academicYear->is($academicYear));
    }

    public function test_school_has_many_classes(): void
    {
        $school = School::factory()->create();
        $schoolClass = SchoolClass::factory()->forSchool($school)->create();

        $this->assertCount(1, $school->classes()->get());
        $this->assertTrue($school->classes->contains($schoolClass));
    }

    public function test_academic_year_has_many_classes(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $schoolClass = SchoolClass::factory()->create([
            'academic_year_id' => $academicYear->id,
        ]);

        $this->assertCount(1, $academicYear->classes()->get());
        $this->assertTrue($academicYear->classes->contains($schoolClass));
    }

    public function test_class_is_inactive_by_default(): void
    {
        $schoolClass = SchoolClass::factory()->create();

        $this->assertFalse($schoolClass->is_active);
    }

    public function test_user_can_resolve_accessible_school_ids(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->assertSame([$school->id], $headmaster->accessibleSchoolIds());

        $administrator = User::factory()->administrator()->create();
        School::factory()->create();

        $this->assertNotSame([], $administrator->accessibleSchoolIds());
    }
}
