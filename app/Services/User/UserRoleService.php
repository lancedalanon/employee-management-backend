<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserRoleService
{
    /**
     * Get the user's full/part-time role.
     *
     * @param User $user
     * @return string|null
     */
    public function getUserEmploymentRole(User $user): ?string
    {
        $roles = $user->getRoleNames();
        $employmentRoles = ['full-time', 'part-time'];

        foreach ($employmentRoles as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Get the user's shift role.
     *
     * @param User $user
     * @return string|null
     */
    public function getUserShiftRole(User $user): ?string
    {
        $roles = $user->getRoleNames();
        $shiftRoles = ['day-shift', 'afternoon-shift', 'evening-shift', 'early-shift', 'late-shift'];

        foreach ($shiftRoles as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return null;
    }
}
