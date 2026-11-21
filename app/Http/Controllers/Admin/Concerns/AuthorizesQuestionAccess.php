<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\QuestionBank;
use App\Models\User;

trait AuthorizesQuestionAccess
{
    /**
     * Determine whether the user may access the question module.
     */
    private function authorizeQuestionAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isGuru() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to questions.
     */
    private function authorizeQuestionManage(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isAdministrator() || $user->isSchoolAdmin() || $user->isRestrictedGuru()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may access the given question bank.
     */
    private function authorizeRecordAccess(?User $user, QuestionBank $questionBank): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! in_array((int) $questionBank->school_id, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }

        if ($user->isRestrictedGuru()
            && ! in_array((int) $questionBank->subject_id, $user->taughtSubjectIds((int) $questionBank->academic_year_id), true)) {
            abort(403, 'Anda hanya dapat mengakses bank soal mata pelajaran yang Anda ampu.');
        }
    }
}
