<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_teacher_belongs_to_school_class_academic_year_subject_and_teacher(): void
    {
        $schoolClass = SchoolClass::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $subject = Subject::factory()->create();
        $teacher = User::factory()->guru()->create();

        $subjectTeacher = SubjectTeacher::factory()->create([
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue($subjectTeacher->schoolClass->is($schoolClass));
        $this->assertTrue($subjectTeacher->academicYear->is($academicYear));
        $this->assertTrue($subjectTeacher->subject->is($subject));
        $this->assertTrue($subjectTeacher->teacher->is($teacher));
    }

    public function test_subject_teacher_is_inactive_by_default(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create();

        $this->assertFalse($subjectTeacher->is_active);
    }
}
