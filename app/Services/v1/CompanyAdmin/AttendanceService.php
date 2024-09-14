<?php

namespace App\Services\v1\CompanyAdmin;

use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    protected $excludedRoles;
    protected $roles;

    public function __construct()
    {
        $this->excludedRoles = ['admin', 'super', 'intern'];
        $this->roles = ['intern', 'employee', 'company_admin', 'company_supervisor'];
    }

    public function index(array $validatedData)
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort'];
        $order = $validatedData['order'];
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];
        $employmentType = $validatedData['employment_type'] ?? null;
        $role = $validatedData['role'] ?? null;
        $startDate = $validatedData['start_date'] ?? null;
        $endDate = $validatedData['end_date'] ?? null;

        // Ensure the date range is within the current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Parse the start and end dates, defaulting to the start and end of the month
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $startOfMonth;
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $endOfMonth;

        // Validate that the date range is within the current month
        if ($startDate->lt($startOfMonth) || $endDate->gt($endOfMonth)) {
            return response()->json([
                'message' => 'Date range must be within the current month.',
            ], 400);
        }

        // Build the query
        $query = User::role($employmentType)
            ->role($role)
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', $this->excludedRoles);
            })
            ->withCount(['dtrs as dtr_attendance_count' => function ($query) use ($startDate, $endDate) {
                $query->whereNull('dtr_absence_date')
                    ->whereNull('dtr_absence_reason')
                    ->whereBetween('dtr_time_in', [$startDate, $endDate]);
            }]);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('user_id', 'LIKE', "%$search%")
                    ->orWhere('first_name', 'LIKE', "%$search%")
                    ->orWhere('middle_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('suffix', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('phone_number', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $userAttendances = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $userAttendances->isEmpty() ? 'No users attendance found for the provided criteria.' : 'Users attendance retrieved successfully.',
            'data' => $userAttendances,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function show(array $validatedData, int $userId)
    {
        // Get the validated query parameters for filtering
        $employmentType = $validatedData['employment_type'] ?? null;
        $role = $validatedData['role'] ?? null;
        $startDate = $validatedData['start_date'] ?? null;
        $endDate = $validatedData['end_date'] ?? null;

        // Ensure the date range is within the current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Parse the start and end dates, defaulting to the start and end of the month
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $startOfMonth;
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $endOfMonth;

        // Validate that the date range is within the current month
        if ($startDate->lt($startOfMonth) || $endDate->gt($endOfMonth)) {
            return response()->json([
                'message' => 'Date range must be within the current month.',
            ], 400);
        }

        // Get the user attendance based on the provided criteria
        $userAttendance = User::where('user_id', $userId)
            ->role($employmentType)
            ->role($role)
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', $this->excludedRoles);
            })
            ->withCount(['dtrs as dtr_attendance_count' => function ($query) use ($startDate, $endDate) {
                $query->whereNull('dtr_absence_date')
                    ->whereNull('dtr_absence_reason')
                    ->whereBetween('dtr_time_in', [$startDate, $endDate]);
            }])
            ->first();

        // Return a 404 response if the user attendance is not found
        if (! $userAttendance) {
            return response()->json([
                'message' => 'User attendance not found.',
            ], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'User attendance retrieved successfully.',
            'data' => $userAttendance,
        ], 200);
    }
}
