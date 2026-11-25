<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuestionBankControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Subject $subject;

    private SchoolClass $schoolClass;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::factory()->active()->create();
        $this->school = School::factory()->create();
        $this->subject = Subject::factory()->create();
        $this->schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        SubjectTeacher::factory()
            ->forClass($this->schoolClass, $this->academicYear, $this->subject)
            ->create(['is_active' => true]);

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_question_banks(): void
    {
        QuestionBank::factory()->count(12)->create();

        $this->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('QuestionBanks/Index')
                ->has('questionBanks.data', 10)
                ->where('questionBanks.total', 12)
                ->has('schools')
                ->has('subjects')
                ->has('examTypes')
                ->has('classes')
                ->has('types', 5)
                ->has('stats')
            );
    }

    public function test_index_filters_question_banks(): void
    {
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'code' => 'BSK-MAT-01',
            'name' => 'Bank Soal Matematika',
            'is_active' => true,
        ]);
        QuestionBank::factory()->create(['is_active' => false]);

        $this->get(route('admin.question-banks.index', [
            'search' => 'Matematika',
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'status' => 'active',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('questionBanks.data', 1)
                ->where('questionBanks.data.0.id', $questionBank->id)
                ->where('filters.status', 'active')
            );
    }

    public function test_index_scopes_question_banks_to_accessible_schools(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $ownSchool = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();

        $mine = QuestionBank::factory()->create(['school_id' => $ownSchool->id]);
        QuestionBank::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('questionBanks.data', 1)
                ->where('questionBanks.data.0.id', $mine->id)
                ->where('stats.total', 1)
            );
    }

    public function test_index_allows_guru_of_the_accessible_school(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);

        $this->actingAs($guru)
            ->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('QuestionBanks/Index'));
    }

    public function test_guru_only_sees_question_banks_of_the_subjects_they_teach(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);
        $otherSubject = Subject::factory()->create();
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        SubjectTeacher::query()->updateOrCreate(
            [
                'school_class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ],
            [
                'academic_year_id' => $this->academicYear->id,
                'teacher_id' => $guru->id,
                'is_active' => true,
            ],
        );

        SubjectTeacher::factory()
            ->forClass($otherClass, $this->academicYear, $otherSubject)
            ->create(['is_active' => true]);

        $ownBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->actingAs($guru)
            ->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('questionBanks.data', 1)
                ->where('questionBanks.data.0.id', $ownBank->id)
                ->has('subjects', 1)
                ->where('subjects.0.id', $this->subject->id)
                ->has('classes', 1)
                ->where('classes.0.id', $this->schoolClass->id)
                ->where('subjectOptions.0.subject_ids', [$this->subject->id])
            );
    }

    public function test_guru_can_manage_a_question_bank_of_a_taught_subject(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);

        SubjectTeacher::query()->updateOrCreate(
            [
                'school_class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ],
            [
                'academic_year_id' => $this->academicYear->id,
                'teacher_id' => $guru->id,
                'is_active' => true,
            ],
        );

        $this->actingAs($guru)
            ->post(route('admin.question-banks.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $questionBank = QuestionBank::query()->firstOrFail();

        $this->actingAs($guru)
            ->put(route('admin.question-banks.update', $questionBank), $this->payload([
                'name' => 'Bank Soal Revisi',
            ]))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame('Bank Soal Revisi', $questionBank->refresh()->name);

        $this->actingAs($guru)
            ->delete(route('admin.question-banks.destroy', $questionBank))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('question_banks', ['id' => $questionBank->id]);
    }

    public function test_headmaster_guru_can_manage_a_question_bank_of_a_taught_subject(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);
        $this->school->update(['headmaster_id' => $guru->id]);

        SubjectTeacher::query()->updateOrCreate(
            [
                'school_class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ],
            [
                'academic_year_id' => $this->academicYear->id,
                'teacher_id' => $guru->id,
                'is_active' => true,
            ],
        );

        $this->actingAs($guru)
            ->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('subjectOptions.0.school_id', $this->school->id)
                ->where('subjectOptions.0.subject_ids', [$this->subject->id])
                ->where('subjectOptions.0.class_ids_by_subject.'.$this->subject->id, [$this->schoolClass->id])
            );

        $this->actingAs($guru)
            ->post(route('admin.question-banks.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $questionBank = QuestionBank::query()->firstOrFail();

        $this->assertSame($this->school->id, $questionBank->school_id);
        $this->assertSame($this->subject->id, $questionBank->subject_id);
    }

    public function test_guru_cannot_store_a_question_bank_of_an_untaught_subject(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);

        $this->actingAs($guru)
            ->post(route('admin.question-banks.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_guru_cannot_store_a_question_bank_for_a_class_they_do_not_teach(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        SubjectTeacher::query()->updateOrCreate(
            [
                'school_class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ],
            [
                'academic_year_id' => $this->academicYear->id,
                'teacher_id' => $guru->id,
                'is_active' => true,
            ],
        );

        $this->actingAs($guru)
            ->post(route('admin.question-banks.store'), $this->payload([
                'class_ids' => [$otherClass->id],
            ]))
            ->assertForbidden();

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_guru_cannot_manage_a_question_bank_of_an_untaught_subject(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);
        $otherSubject = Subject::factory()->create();
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        SubjectTeacher::query()->updateOrCreate(
            [
                'school_class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ],
            [
                'academic_year_id' => $this->academicYear->id,
                'teacher_id' => $guru->id,
                'is_active' => true,
            ],
        );

        SubjectTeacher::factory()
            ->forClass($otherClass, $this->academicYear, $otherSubject)
            ->create(['is_active' => true]);

        $questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->actingAs($guru)
            ->put(route('admin.question-banks.update', $questionBank), $this->payload([
                'subject_id' => $otherSubject->id,
            ]))
            ->assertForbidden();

        $this->actingAs($guru)
            ->delete(route('admin.question-banks.destroy', $questionBank))
            ->assertForbidden();
    }

    public function test_store_creates_a_question_bank_with_classes(): void
    {
        $secondClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'class_ids' => [$this->schoolClass->id, $secondClass->id],
        ]))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $questionBank = QuestionBank::query()->firstOrFail();

        $this->assertSame($this->school->id, $questionBank->school_id);
        $this->assertSame($this->subject->id, $questionBank->subject_id);
        $this->assertSame('BSK-MAT-01', $questionBank->code);
        $this->assertSame(20, $questionBank->pilihan_ganda_count);
        $this->assertSame(10, $questionBank->pilihan_ganda_kompleks_count);
        $this->assertSame(10, $questionBank->menjodohkan_count);
        $this->assertSame(10, $questionBank->isian_count);
        $this->assertSame(5, $questionBank->esai_count);
        $this->assertSame(55, $questionBank->total_question_count);
        $this->assertCount(2, $questionBank->classes);
    }

    public function test_store_saves_the_scores_and_the_option_counts(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $questionBank = QuestionBank::query()->firstOrFail();

        $this->assertSame(50.0, $questionBank->pilihan_ganda_score);
        $this->assertSame(30.0, $questionBank->pilihan_ganda_kompleks_score);
        $this->assertSame(40.0, $questionBank->menjodohkan_score);
        $this->assertSame(20.0, $questionBank->isian_score);
        $this->assertSame(10.0, $questionBank->esai_score);
        $this->assertSame(4, $questionBank->pilihan_ganda_option_count);
        $this->assertSame(5, $questionBank->pilihan_ganda_kompleks_option_count);
        $this->assertSame([
            'pilihan_ganda' => 50.0,
            'pilihan_ganda_kompleks' => 30.0,
            'menjodohkan' => 40.0,
            'isian_singkat' => 20.0,
            'esai' => 10.0,
        ], $questionBank->scores);
    }

    public function test_store_rejects_an_option_count_outside_the_allowed_range(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload([
            'pilihan_ganda_option_count' => 2,
        ]))->assertSessionHasErrors('pilihan_ganda_option_count');

        $this->post(route('admin.question-banks.store'), $this->payload([
            'pilihan_ganda_option_count' => 6,
        ]))->assertSessionHasErrors('pilihan_ganda_option_count');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_store_requires_the_scores(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload([
            'pilihan_ganda_score' => null,
        ]))->assertSessionHasErrors('pilihan_ganda_score');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_store_saves_the_exam_type(): void
    {
        $examType = ExamType::factory()->active()->create([
            'school_id' => $this->school->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'exam_type_id' => $examType->id,
        ]))->assertRedirect()->assertSessionHas('toast.type', 'success');

        $questionBank = QuestionBank::query()->firstOrFail();

        $this->assertSame($examType->id, $questionBank->exam_type_id);
    }

    public function test_store_rejects_an_inactive_exam_type(): void
    {
        $examType = ExamType::factory()->inactive()->create([
            'school_id' => $this->school->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'exam_type_id' => $examType->id,
        ]))->assertSessionHasErrors('exam_type_id');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_store_rejects_an_exam_type_from_another_school(): void
    {
        $examType = ExamType::factory()->active()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'exam_type_id' => $examType->id,
        ]))->assertSessionHasErrors('exam_type_id');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_update_keeps_the_current_exam_type_even_when_it_becomes_inactive(): void
    {
        $examType = ExamType::factory()->active()->create([
            'school_id' => $this->school->id,
        ]);

        $questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $examType->id,
        ]);

        $examType->update(['is_active' => false]);

        $this->put(
            route('admin.question-banks.update', $questionBank),
            $this->payload(['exam_type_id' => $examType->id])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertSame($examType->id, $questionBank->refresh()->exam_type_id);
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'BSK-MAT-01',
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload())
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('question_banks', 1);
    }

    public function test_store_rejects_class_from_another_school(): void
    {
        $otherClass = SchoolClass::factory()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'class_ids' => [$otherClass->id],
        ]))->assertSessionHasErrors('class_ids');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_store_requires_at_least_one_class(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload([
            'class_ids' => [],
        ]))->assertSessionHasErrors('class_ids');
    }

    public function test_store_requires_a_non_empty_composition(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload([
            'pilihan_ganda_count' => 0,
            'pilihan_ganda_kompleks_count' => 0,
            'menjodohkan_count' => 0,
            'isian_count' => 0,
            'esai_count' => 0,
        ]))->assertSessionHasErrors('composition');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_school_admin_cannot_store_for_an_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        SubjectTeacher::factory()
            ->forClass($otherClass, $this->academicYear, $this->subject)
            ->create(['is_active' => true]);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.question-banks.store'), $this->payload([
                'school_id' => $otherSchool->id,
                'class_ids' => [$otherClass->id],
            ]))
            ->assertForbidden();
    }

    public function test_headmaster_can_view_but_cannot_manage_question_banks(): void
    {
        $headmaster = User::factory()->guru()->create();
        $headmasterSchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $headmasterSchool->id,
        ]);

        $this->actingAs($headmaster)
            ->get(route('admin.question-banks.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->delete(route('admin.question-banks.destroy', $questionBank))
            ->assertForbidden();
    }

    public function test_update_changes_composition_and_syncs_classes(): void
    {
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'code' => 'BSK-MAT-01',
        ]);
        $questionBank->classes()->sync([$this->schoolClass->id]);

        $secondClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->put(route('admin.question-banks.update', $questionBank), $this->payload([
            'pilihan_ganda_count' => 30,
            'pilihan_ganda_kompleks_count' => 0,
            'menjodohkan_count' => 0,
            'isian_count' => 0,
            'esai_count' => 0,
            'class_ids' => [$secondClass->id],
        ]))->assertRedirect()->assertSessionHas('toast.type', 'success');

        $questionBank->refresh();

        $this->assertSame(30, $questionBank->pilihan_ganda_count);
        $this->assertSame(0, $questionBank->pilihan_ganda_kompleks_count);
        $this->assertSame(30, $questionBank->total_question_count);
        $this->assertSame([$secondClass->id], $questionBank->classes->pluck('id')->all());
    }

    public function test_destroy_deletes_the_question_bank_and_its_classes(): void
    {
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $questionBank->classes()->sync([$this->schoolClass->id]);

        $this->delete(route('admin.question-banks.destroy', $questionBank))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('question_banks', ['id' => $questionBank->id]);
        $this->assertDatabaseMissing('question_bank_class', [
            'question_bank_id' => $questionBank->id,
        ]);
    }

    public function test_index_scopes_question_banks_to_the_active_academic_year(): void
    {
        $pastYear = AcademicYear::factory()->inactive()->create();

        $activeBank = QuestionBank::factory()->create(['school_id' => $this->school->id]);
        QuestionBank::factory()
            ->forAcademicYear($pastYear)
            ->create(['school_id' => $this->school->id]);

        $this->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('questionBanks.data', 1)
                ->where('questionBanks.data.0.id', $activeBank->id)
                ->where('stats.total', 1)
                ->where('activeAcademicYear.id', $this->academicYear->id)
                ->where('questionBanks.data.0.academic_year_name', $this->academicYear->name)
            );
    }

    public function test_index_only_offers_the_subjects_mapped_in_the_active_year(): void
    {
        $this->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('subjectOptions', 1)
                ->where('subjectOptions.0.school_id', $this->school->id)
                ->where('subjectOptions.0.subject_ids', [$this->subject->id])
            );
    }

    public function test_index_only_offers_the_classes_of_the_active_year(): void
    {
        $pastYear = AcademicYear::factory()->inactive()->create();
        $pastClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
        ]);

        $this->get(route('admin.question-banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes', 1)
                ->where('classes.0.id', $this->schoolClass->id)
            );
    }

    public function test_store_assigns_the_active_academic_year(): void
    {
        $this->post(route('admin.question-banks.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame(
            $this->academicYear->id,
            QuestionBank::query()->firstOrFail()->academic_year_id
        );
    }

    public function test_update_keeps_the_original_academic_year(): void
    {
        $pastYear = AcademicYear::factory()->inactive()->create();
        $questionBank = QuestionBank::factory()
            ->forAcademicYear($pastYear)
            ->create([
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'code' => 'BSK-MAT-01',
            ]);

        $this->put(route('admin.question-banks.update', $questionBank), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame($pastYear->id, $questionBank->refresh()->academic_year_id);
    }

    public function test_store_rejects_a_subject_that_is_not_mapped_for_the_school(): void
    {
        $unmappedSubject = Subject::factory()->create();

        $this->post(route('admin.question-banks.store'), $this->payload([
            'subject_id' => $unmappedSubject->id,
        ]))->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('question_banks', 0);
    }

    public function test_store_rejects_a_class_from_another_academic_year(): void
    {
        $pastYear = AcademicYear::factory()->inactive()->create();
        $pastClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
        ]);

        $this->post(route('admin.question-banks.store'), $this->payload([
            'class_ids' => [$pastClass->id],
        ]))->assertSessionHasErrors('class_ids');

        $this->assertDatabaseCount('question_banks', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => null,
            'code' => 'BSK-MAT-01',
            'name' => 'Bank Soal Matematika',
            'description' => 'Bank soal untuk asesmen matematika.',
            'pilihan_ganda_count' => 20,
            'pilihan_ganda_kompleks_count' => 10,
            'menjodohkan_count' => 10,
            'isian_count' => 10,
            'esai_count' => 5,
            'pilihan_ganda_score' => 50,
            'pilihan_ganda_kompleks_score' => 30,
            'menjodohkan_score' => 40,
            'isian_score' => 20,
            'esai_score' => 10,
            'pilihan_ganda_option_count' => 4,
            'pilihan_ganda_kompleks_option_count' => 5,
            'class_ids' => [$this->schoolClass->id],
            'is_active' => true,
        ], $overrides);
    }
}
