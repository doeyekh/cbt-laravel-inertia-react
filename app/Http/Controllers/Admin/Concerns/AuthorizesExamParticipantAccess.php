<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ExamParticipant;
use App\Models\User;

trait AuthorizesExamParticipantAccess
{
    /**
     * Determine whether the user may access the exam participant module.
     */
    private function authorizeExamParticipantAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to exam participants.
     */
    private function authorizeExamParticipantManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may use the given school.
     */
    private function authorizeParticipantSchoolAccess(?User $user, int $schoolId): void
    {
        if ($user === null || ! in_array($schoolId, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may access the given participant.
     */
    private function authorizeParticipantAccess(?User $user, ExamParticipant $participant): void
    {
        $this->authorizeParticipantSchoolAccess($user, (int) $participant->school_id);
    }
}
