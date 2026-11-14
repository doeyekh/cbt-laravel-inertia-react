<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SubjectTeacher;
use Illuminate\Validation\Validator;

trait ValidatesQuestionBankScope
{
    /**
     * Get the id of the academic year every question bank must belong to.
     */
    protected function activeAcademicYearId(): int
    {
        return (int) (AcademicYear::active()?->id ?? 0);
    }

    /**
     * Get the ids of the subjects mapped to the school's classes in the given academic year.
     *
     * @return array<int, int>
     */
    protected function mappedSubjectIds(int $schoolId, int $academicYearId): array
    {
        if ($schoolId <= 0 || $academicYearId <= 0) {
            return [];
        }

        return SubjectTeacher::query()
            ->where('subject_teachers.academic_year_id', $academicYearId)
            ->whereIn('school_class_id', SchoolClass::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->select('id'))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Validate that the subject and the classes belong to the school and the active academic year.
     */
    protected function validateQuestionBankScope(Validator $validator): void
    {
        $schoolId = (int) $this->input('school_id');

        if ($schoolId <= 0) {
            return;
        }

        $academicYearId = $this->activeAcademicYearId();
        $subjectId = (int) $this->input('subject_id');

        if (! $validator->errors()->has('subject_id')
            && $subjectId > 0
            && ! in_array($subjectId, $this->mappedSubjectIds($schoolId, $academicYearId), true)) {
            $validator->errors()->add('subject_id', 'Mata pelajaran belum dipetakan pada kelas tahun pelajaran aktif.');
        }

        $classIds = collect($this->input('class_ids', []))
            ->map(fn ($classId): int => (int) $classId)
            ->unique()
            ->values()
            ->all();

        if ($classIds === []) {
            return;
        }

        $classes = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->get(['id', 'school_id', 'academic_year_id']);

        if ($classes->contains(fn (SchoolClass $schoolClass): bool => (int) $schoolClass->school_id !== $schoolId)) {
            $validator->errors()->add('class_ids', 'Kelas yang dipilih harus berasal dari sekolah yang dipilih.');

            return;
        }

        if ($academicYearId > 0
            && $classes->contains(fn (SchoolClass $schoolClass): bool => (int) $schoolClass->academic_year_id !== $academicYearId)) {
            $validator->errors()->add('class_ids', 'Kelas yang dipilih harus berasal dari tahun pelajaran aktif.');
        }
    }
}
