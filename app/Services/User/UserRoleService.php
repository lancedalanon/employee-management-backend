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
    /**
     * Check if the authenticated user has either the 'admin' or 'super' role.
     *
     * @return bool Returns true if the user has either the 'admin' or 'super' role, otherwise false.
     */
    public function hasAdminRole(): bool
    {
        $user = Auth::user();

        if ($user->hasRole('admin') || $user->hasRole('super')) {
            return true;
        } else {
            return false;
        }
    }
}
