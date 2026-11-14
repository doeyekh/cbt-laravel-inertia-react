<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    /**
     * Determine whether the user can view any subjects.
     * Hanya administrator dan guru yang ditugaskan sebagai admin sekolah yang dapat mengakses.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator()
            || $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can view the subject.
     */
    public function view(User $user, Subject $subject): bool
    {
        return $user->isAdministrator()
            || $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can create subjects.
     * Administrator memiliki fungsi penuh; admin sekolah hanya melihat.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update the subject.
     */
    public function update(User $user, Subject $subject): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete the subject.
     */
    public function delete(User $user, Subject $subject): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can toggle the active status of the subject.
     */
    public function toggleStatus(User $user, Subject $subject): bool
    {
        return $user->isAdministrator();
    }
}
