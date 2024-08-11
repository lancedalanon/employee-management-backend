<?php

namespace App\Services\Admin;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Support\Facades\Response;

class AttendanceService
{    
    public function indexEmployeeFullTime(int $perPage, int $page)
    {
        $roles = ['employee', 'full-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $perPage, $page);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json([
            'message' => 'Full time employee attendances retrieved successfully.',
            'current_page' => $usersWithAttendance->currentPage(),
            'data' => $usersWithAttendance->items(),
            'first_page_url' => $usersWithAttendance->url(1),
            'from' => $usersWithAttendance->firstItem(),
            'last_page' => $usersWithAttendance->lastPage(),
            'last_page_url' => $usersWithAttendance->url($usersWithAttendance->lastPage()),
            'links' => $usersWithAttendance->linkCollection()->toArray(),
            'next_page_url' => $usersWithAttendance->nextPageUrl(),
            'path' => $usersWithAttendance->path(),
            'per_page' => $usersWithAttendance->perPage(),
            'prev_page_url' => $usersWithAttendance->previousPageUrl(),
            'to' => $usersWithAttendance->lastItem(),
            'total' => $usersWithAttendance->total(),
        ], 200);
    }
    
    public function showEmployeeFullTime(int $userId) 
    {
        $roles = ['employee', 'full-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json([
            'message' => 'Full time employee attendance retrieved successfully.',
            'data' => $userWithAttendance,
        ], 200);    
    }     

    public function indexEmployeePartTime(int $perPage, int $page)
    {
        $roles = ['employee', 'part-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $perPage, $page);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json([
            'message' => 'Part time employee attendances retrieved successfully.',
            'current_page' => $usersWithAttendance->currentPage(),
            'data' => $usersWithAttendance->items(),
            'first_page_url' => $usersWithAttendance->url(1),
            'from' => $usersWithAttendance->firstItem(),
            'last_page' => $usersWithAttendance->lastPage(),
            'last_page_url' => $usersWithAttendance->url($usersWithAttendance->lastPage()),
            'links' => $usersWithAttendance->linkCollection()->toArray(),
            'next_page_url' => $usersWithAttendance->nextPageUrl(),
            'path' => $usersWithAttendance->path(),
            'per_page' => $usersWithAttendance->perPage(),
            'prev_page_url' => $usersWithAttendance->previousPageUrl(),
            'to' => $usersWithAttendance->lastItem(),
            'total' => $usersWithAttendance->total(),
        ], 200);    
    }

    public function showEmployeePartTime(int $userId) 
    {
        $roles = ['employee', 'part-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json([
            'message' => 'Part time employee attendance retrieved successfully.',
            'data' => $userWithAttendance,
        ], 200);    
    }

    public function indexInternFullTime(int $perPage, int $page)
    {
        $roles = ['intern', 'full-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $perPage, $page);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json([
            'message' => 'Full time intern attendances retrieved successfully.',
            'current_page' => $usersWithAttendance->currentPage(),
            'data' => $usersWithAttendance->items(),
            'first_page_url' => $usersWithAttendance->url(1),
            'from' => $usersWithAttendance->firstItem(),
            'last_page' => $usersWithAttendance->lastPage(),
            'last_page_url' => $usersWithAttendance->url($usersWithAttendance->lastPage()),
            'links' => $usersWithAttendance->linkCollection()->toArray(),
            'next_page_url' => $usersWithAttendance->nextPageUrl(),
            'path' => $usersWithAttendance->path(),
            'per_page' => $usersWithAttendance->perPage(),
            'prev_page_url' => $usersWithAttendance->previousPageUrl(),
            'to' => $usersWithAttendance->lastItem(),
            'total' => $usersWithAttendance->total(),
        ], 200);
    }
    
    public function showInternFullTime(int $userId) 
    {
        $roles = ['intern', 'full-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json([
            'message' => 'Full time intern attendance retrieved successfully.',
            'data' => $userWithAttendance,
        ], 200);
    }

    public function indexInternPartTime(int $perPage, int $page)
    {
        $roles = ['intern', 'part-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles,  $perPage, $page);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json([
            'message' => 'Part time intern attendances retrieved successfully.',
            'current_page' => $usersWithAttendance->currentPage(),
            'data' => $usersWithAttendance->items(),
            'first_page_url' => $usersWithAttendance->url(1),
            'from' => $usersWithAttendance->firstItem(),
            'last_page' => $usersWithAttendance->lastPage(),
            'last_page_url' => $usersWithAttendance->url($usersWithAttendance->lastPage()),
            'links' => $usersWithAttendance->linkCollection()->toArray(),
            'next_page_url' => $usersWithAttendance->nextPageUrl(),
            'path' => $usersWithAttendance->path(),
            'per_page' => $usersWithAttendance->perPage(),
            'prev_page_url' => $usersWithAttendance->previousPageUrl(),
            'to' => $usersWithAttendance->lastItem(),
            'total' => $usersWithAttendance->total(),
        ], 200);    
    }

    public function showInternPartTime(int $userId) 
    {
        $roles = ['intern', 'part-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json([
            'message' => 'Part time intern attendance retrieved successfully.',
            'data' => $userWithAttendance,
        ], 200);    
    }

    protected function fetchUsersWithRoles(array $roles, array $excludedRoles, int $perPage, int $page)
    {
        return User::with('roles')
            ->role($roles)
            ->whereDoesntHave('roles', function ($query) use ($excludedRoles) {
                $query->whereIn('name', $excludedRoles);
            })
            ->withCount(['dtrs as dtr_attendance_count' => function ($query) {
                $query->whereNull('absence_date')
                    ->whereNull('absence_reason');
            }])
            ->paginate($perPage, ['*'], 'page', $page);
    }

    protected function fetchUserWithRole(int $userId, array $roles, array $excludedRoles)
    {
        $user = User::with('roles')
            ->role($roles)
            ->where('user_id', $userId)
            ->whereDoesntHave('roles', function ($query) use ($excludedRoles) {
                $query->whereIn('name', $excludedRoles);
            })
            ->first();

        if (!$user) {
            return Response::json(['message' => 'User not found.'], 404);
        }

        $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
            return in_array($role->name, $roles);
        });

        $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
        unset($user->roles);

        $dtrAttendanceCount = Dtr::where('user_id', $userId)
            ->whereNull('absence_date')
            ->whereNull('absence_reason')
            ->count();

        $user->dtr_attendance_count = $dtrAttendanceCount;

        return $user;
    }
}