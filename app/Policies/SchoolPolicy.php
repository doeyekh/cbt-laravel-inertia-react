<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view any schools.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator()
            || $user->isHeadmaster()
            || $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can view the specified school.
     */
    public function view(User $user, School $school): bool
    {
        return $user->isAdministrator()
            || $user->isHeadmasterOf($school)
            || $user->isSchoolAdminOf($school);
    }

    /**
     * Determine whether the user can create schools.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update the specified school.
     * Administrator memiliki hak penuh, admin sekolah hanya bisa mengedit sekolahnya sendiri.
     * Kepala sekolah tidak memiliki hak edit.
     */
    public function update(User $user, School $school): bool
    {
        return $user->isAdministrator()
            || $user->isSchoolAdminOf($school);
    }

    /**
     * Determine whether the user can delete the specified school.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can toggle the active status of the specified school.
     */
    public function toggleStatus(User $user, School $school): bool
    {
        return $user->isAdministrator();
    }
}
