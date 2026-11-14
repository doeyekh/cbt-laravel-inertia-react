<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubjectTeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_subject_teachers_for_the_class(): void
    {
        $schoolClass = SchoolClass::factory()->create();
        SubjectTeacher::factory()->count(3)->forClass($schoolClass)->create();

        $this->get(route('admin.subject-teachers.index', ['schoolClass' => $schoolClass->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SubjectTeachers/Index')
                ->has('subjectTeachers.data', 3)
                ->where('stats.total', 3)
            );
    }

    public function test_index_returns_empty_subject_teachers_when_class_has_none(): void
    {
        $schoolClass = SchoolClass::factory()->create();

        $this->get(route('admin.subject-teachers.index', ['schoolClass' => $schoolClass->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('subjectTeachers.data', 0)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.subject-teachers.index', ['schoolClass' => SchoolClass::factory()->create()->id]))
            ->assertForbidden();
    }

    public function test_index_allows_headmaster_of_the_class_school(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $schoolClass = SchoolClass::factory()->forSchool($school)->create();
        SubjectTeacher::factory()->forClass($schoolClass)->create();

        $this->actingAs($headmaster)
            ->get(route('admin.subject-teachers.index', ['schoolClass' => $schoolClass->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('subjectTeachers.data', 1)
            );
    }

    public function test_store_creates_subject_teacher_assignment(): void
    {
        $schoolClass = SchoolClass::factory()->create();
        $academicYearId = $schoolClass->academic_year_id;
        $subject = Subject::factory()->create();
        $teacher = User::factory()->guru()->create();

        $this->post(route('admin.subject-teachers.store', ['schoolClass' => $schoolClass->id]), [
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ])->assertRedirect();

        $subjectTeacher = SubjectTeacher::query()
            ->where('school_class_id', $schoolClass->id)
            ->first();

        $this->assertNotNull($subjectTeacher);
        $this->assertSame($academicYearId, $subjectTeacher->academic_year_id);
        $this->assertSame($subject->id, $subjectTeacher->subject_id);
        $this->assertSame($teacher->id, $subjectTeacher->teacher_id);
        $this->assertTrue($subjectTeacher->is_active);
    }

    public function test_store_requires_subject_and_teacher(): void
    {
        $this->post(route('admin.subject-teachers.store', ['schoolClass' => SchoolClass::factory()->create()->id]), [])
            ->assertSessionHasErrors(['subject_id', 'teacher_id']);
    }

    public function test_store_rejects_duplicate_subject_for_the_class(): void
    {
        $schoolClass = SchoolClass::factory()->create();
        $subject = Subject::factory()->create();

        SubjectTeacher::factory()->forClass($schoolClass, null, $subject)->create();

        $this->post(route('admin.subject-teachers.store', ['schoolClass' => $schoolClass->id]), [
            'subject_id' => $subject->id,
            'teacher_id' => User::factory()->guru()->create()->id,
        ])->assertSessionHasErrors('subject_id');

        $this->assertSame(
            1,
            SubjectTeacher::query()->where('school_class_id', $schoolClass->id)->count()
        );
    }

    public function test_store_rejects_class_of_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $schoolClass = SchoolClass::factory()->forSchool($school)->create();

        $otherAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $otherAdmin->id]);

        $this->actingAs($otherAdmin)
            ->post(route('admin.subject-teachers.store', ['schoolClass' => $schoolClass->id]), [
                'subject_id' => Subject::factory()->create()->id,
                'teacher_id' => User::factory()->guru()->create()->id,
            ])
            ->assertForbidden();
    }

    public function test_update_changes_teacher(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create();

        $newTeacher = User::factory()->guru()->create();

        $this->put(route('admin.subject-teachers.update', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]), [
            'teacher_id' => $newTeacher->id,
        ])->assertRedirect();

        $this->assertSame($newTeacher->id, $subjectTeacher->fresh()->teacher_id);
    }

    public function test_update_validates_teacher(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create();

        $this->put(route('admin.subject-teachers.update', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]), [
            'teacher_id' => 999999,
        ])->assertSessionHasErrors('teacher_id');
    }

    public function test_toggle_status_toggles_subject_teacher(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create(['is_active' => false]);

        $this->patch(route('admin.subject-teachers.toggle-status', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($subjectTeacher->fresh()->is_active);

        $this->patch(route('admin.subject-teachers.toggle-status', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]))
            ->assertRedirect();

        $this->assertFalse($subjectTeacher->fresh()->is_active);
    }

    public function test_destroy_rejects_active_subject_teacher(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create(['is_active' => true]);

        $this->delete(route('admin.subject-teachers.destroy', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'error');

        $this->assertDatabaseHas('subject_teachers', [
            'id' => $subjectTeacher->id,
        ]);
    }

    public function test_destroy_deletes_inactive_subject_teacher(): void
    {
        $subjectTeacher = SubjectTeacher::factory()->create(['is_active' => false]);

        $this->delete(route('admin.subject-teachers.destroy', ['schoolClass' => $subjectTeacher->school_class_id, 'subjectTeacher' => $subjectTeacher->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('subject_teachers', [
            'id' => $subjectTeacher->id,
        ]);
    }

    public function test_kepala_sekolah_cannot_manage_subject_teachers(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $schoolClass = SchoolClass::factory()->forSchool($school)->create();

        $this->actingAs($headmaster)
            ->post(route('admin.subject-teachers.store', ['schoolClass' => $schoolClass->id]), [
                'subject_id' => Subject::factory()->create()->id,
                'teacher_id' => User::factory()->guru()->create()->id,
            ])
            ->assertForbidden();
    }
}
