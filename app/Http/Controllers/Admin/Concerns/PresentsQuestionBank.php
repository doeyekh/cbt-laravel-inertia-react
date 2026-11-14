<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\QuestionBank;
use App\Models\SchoolClass;

trait PresentsQuestionBank
{
    /**
     * Transform a question bank into its frontend representation.
     *
     * @return array<string, mixed>
     */
    protected function presentQuestionBank(QuestionBank $questionBank): array
    {
        return [
            'id' => $questionBank->id,
            'school_id' => $questionBank->school_id,
            'school_name' => $questionBank->school?->name,
            'school_code' => $questionBank->school?->code,
            'subject_id' => $questionBank->subject_id,
            'subject_name' => $questionBank->subject?->name,
            'subject_code' => $questionBank->subject?->code,
            'academic_year_id' => $questionBank->academic_year_id,
            'academic_year_name' => $questionBank->academicYear?->name,
            'academic_year_code' => $questionBank->academicYear?->code,
            'exam_type_id' => $questionBank->exam_type_id,
            'exam_type_name' => $questionBank->examType?->name,
            'exam_type_code' => $questionBank->examType?->code,
            'code' => $questionBank->code,
            'name' => $questionBank->name,
            'description' => $questionBank->description,
            'composition' => $questionBank->composition,
            'scores' => $questionBank->scores,
            'option_counts' => $questionBank->option_counts,
            'total_question_count' => $questionBank->total_question_count,
            'total_score' => $questionBank->total_score,
            'classes' => $questionBank->classes->map(fn (SchoolClass $schoolClass): array => [
                'id' => $schoolClass->id,
                'code' => $schoolClass->code,
                'name' => $schoolClass->name,
            ])->values()->all(),
            'class_ids' => $questionBank->classes->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'is_active' => $questionBank->is_active,
        ];
    }
}
