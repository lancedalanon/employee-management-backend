<?php

namespace App\Services\Admin;

use App\Models\User;
use Carbon\Carbon;
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

    public function index(array $validatedData, int $perPage, int $page, ?string $startDate, ?string $endDate)
    {
        try {
            // Get the validated query parameters for filtering
            $employmentStatus = $validatedData['employment_status']; // e.g., 'full-time' or 'part-time'
            $personnel = $validatedData['personnel']; // e.g., 'employee' or 'intern'

            // Ensure the date range is within the current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Parse the start and end dates, defaulting to the start and end of the month
            $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $startOfMonth;
            $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $endOfMonth;

            // Validate that the date range is within the current month
            if ($startDate->lt($startOfMonth) || $endDate->gt($endOfMonth)) {
                return Response::json([
                    'message' => 'Date range must be within the current month.',
                ], 400);
            }

            $userAttendances = User::role($employmentStatus)
                ->role($personnel)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', $this->excludedRoles);
                })
                ->withCount(['dtrs as dtr_attendance_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereNull('absence_date')
                        ->whereNull('absence_reason')
                        ->whereBetween('time_in', [$startDate, $endDate]);
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

    public function show(array $validatedData, int $userId, ?string $startDate, ?string $endDate)
    {
        try {
            // Get the validated query parameters for filtering
            $employmentStatus = $validatedData['employment_status']; // e.g., 'full-time' or 'part-time'
            $personnel = $validatedData['personnel']; // e.g., 'employee' or 'intern'

            // Ensure the date range is within the current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Parse the start and end dates, defaulting to the start and end of the month
            $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $startOfMonth;
            $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $endOfMonth;

            // Validate that the date range is within the current month
            if ($startDate->lt($startOfMonth) || $endDate->gt($endOfMonth)) {
                return Response::json([
                    'message' => 'Date range must be within the current month.',
                ], 400);
            }

            $userAttendance = User::where('user_id', $userId)
                ->role($employmentStatus)
                ->role($personnel)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', $this->excludedRoles);
                })
                ->withCount(['dtrs as dtr_attendance_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereNull('absence_date')
                        ->whereNull('absence_reason')
                        ->whereBetween('time_in', [$startDate, $endDate]);
                }])
                ->first();

            if (! $userAttendance) {
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
