<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dtr;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AttendanceController extends Controller
{
    public function indexInternFullTime(Request $request)
    {
        $roles = ['intern', 'full-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $request);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json($usersWithAttendance, 200);
    }
    
    public function showInternFullTime(int $userId) 
    {
        $roles = ['intern', 'full-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json($userWithAttendance, 200);
    }
    
    public function indexEmployeeFullTime(Request $request)
    {
        $roles = ['employee', 'full-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $request);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json($usersWithAttendance, 200);
    }
    
    public function showEmployeeFullTime(int $userId) 
    {
        $roles = ['employee', 'full-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json($userWithAttendance, 200);
    }     

    public function indexInternPartTime(Request $request)
    {
        $roles = ['intern', 'part-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $request);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json($usersWithAttendance, 200);
    }

    public function showInternPartTime(int $userId) 
    {
        $roles = ['intern', 'part-time'];
        $excludedRoles = ['admin', 'super', 'employee'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json($userWithAttendance, 200);
    }

    public function indexEmployeePartTime(Request $request)
    {
        $roles = ['employee', 'part-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $usersWithAttendance = $this->fetchUsersWithRoles($roles, $excludedRoles, $request);
    
        $usersWithAttendance->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });
    
        return Response::json($usersWithAttendance, 200);
    }

    public function showEmployeePartTime(int $userId) 
    {
        $roles = ['employee', 'part-time'];
        $excludedRoles = ['admin', 'super', 'intern'];
    
        $userWithAttendance = $this->fetchUserWithRole($userId, $roles, $excludedRoles);
    
        if ($userWithAttendance instanceof \Illuminate\Http\JsonResponse) {
            return $userWithAttendance; // Return the 404 response if user not found
        }
    
        return Response::json($userWithAttendance, 200);
    }

    protected function fetchUsersWithRoles(array $roles, array $excludedRoles, Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        return User::with('roles')
            ->role($roles)
            ->whereDoesntHave('roles', function ($query) use ($excludedRoles) {
                $query->whereIn('name', $excludedRoles);
            })
            ->withCount(['dtrs as dtrs_attendance_count' => function ($query) {
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
            return Response::json(['message' => 'User not found'], 404);
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
