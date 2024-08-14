<?php

namespace App\Services\Admin;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class AttendanceService
{    
    protected $excludedRoles;
    protected $roles;

    public function __construct()
    {
        $this->excludedRoles = ['admin', 'super', 'intern'];
        $this->roles = ['intern', 'employee'];
    }

    public function index(string $employmentStatus, string $personnel, int $perPage, int $page)
    {
        try {
            $userAttendances = User::role($employmentStatus)
                ->role($personnel)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', $this->excludedRoles);
                })
                ->withCount(['dtrs as dtr_attendance_count' => function ($query) {
                    $query->whereNull('absence_date')
                        ->whereNull('absence_reason');
                }])
                ->paginate($perPage, ['*'], 'page', $page);

            return Response::json([
                'message' => 'User attendances retrieved successfully.',
                'current_page' => $userAttendances->currentPage(),
                'data' => $userAttendances->items(),
                'first_page_url' => $userAttendances->url(1),
                'from' => $userAttendances->firstItem(),
                'last_page' => $userAttendances->lastPage(),
                'last_page_url' => $userAttendances->url($userAttendances->lastPage()),
                'links' => $userAttendances->linkCollection()->toArray(),
                'next_page_url' => $userAttendances->nextPageUrl(),
                'path' => $userAttendances->path(),
                'per_page' => $userAttendances->perPage(),
                'prev_page_url' => $userAttendances->previousPageUrl(),
                'to' => $userAttendances->lastItem(),
                'total' => $userAttendances->total(),
            ], 200);  
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving attendances.',
            ], 500);
        }
    }

    public function show(string $employmentStatus, string $personnel, int $userId)
    {
        try {
            $userAttendance = User::where('user_id', $userId)
            ->role($employmentStatus)
            ->role($personnel)
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', $this->excludedRoles);
            })
            ->withCount(['dtrs as dtr_attendance_count' => function ($query) {
                $query->whereNull('absence_date')
                    ->whereNull('absence_reason');
            }])
            ->first();

            if (!$userAttendance) {
                return Response::json([
                   'message' => 'User attendance not found.',
                ], 404);
            }

            return Response::json([
                'message' => 'User attendance retrieved successfully.',
                'data' => $userAttendance,
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving attendance.',
            ], 500);
        }
    }
}