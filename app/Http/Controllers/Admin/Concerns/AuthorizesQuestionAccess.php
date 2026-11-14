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

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
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
    }
}
