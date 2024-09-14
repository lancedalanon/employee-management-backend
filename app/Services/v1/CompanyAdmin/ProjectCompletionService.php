<?php

namespace App\Services\v1\CompanyAdmin;

use App\Models\User;
use Carbon\Carbon;

class ProjectCompletionService
{
    protected $excludedRoles;
    protected $roles;

    public function __construct()
    {
        $this->excludedRoles = ['admin', 'super', 'intern', 'company_admin', 'company_supervisor'];
        $this->roles = ['intern', 'employee'];
    }

    public function index(array $validatedData)
    {
        // Retrieve query parameters from the request
        $sort = $validatedData['sort'];
        $order = $validatedData['order'];
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];
        $employmentType = $validatedData['employment_type'] ?? null;
        $role = $validatedData['role'] ?? null;
        $startDate = $validatedData['start_date'] ?? null;
        $endDate = $validatedData['end_date'] ?? null;

        // Define the start and end of the current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Parse start and end dates, default to the start and end of the month
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $startOfMonth;
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $endOfMonth;

        // Validate that the date range is within the current month
        if ($startDate->lt($startOfMonth) || $endDate->gt($endOfMonth)) {
            return response()->json([
                'message' => 'Date range must be within the current month.',
            ], 400);
        }

        // Build the query with counts for different task statuses
        $query = User::withCount([
            'tasks as tasks_not_started_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Not started')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_in_progress_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'In progress')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_reviewing_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Reviewing')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_backlog_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Backlog')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_completed_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_not_started_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Not started')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_in_progress_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'In progress')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_reviewing_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Reviewing')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_backlog_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Backlog')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_completed_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
        ])
        ->role($employmentType)
        ->role($role)
        ->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', $this->excludedRoles);
        });
        
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
        $userProjectCompletion = $query->paginate($perPage, ['*'], 'page', $page);

        // Map the results to a simplified structure
        $data = $userProjectCompletion->map(function ($user) {
            return [
                'user_id' => $user->user_id,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'suffix' => $user->suffix,
                'username' => $user->username,
                'tasks_not_started_count' => $user->tasks_not_started_count,
                'tasks_in_progress_count' => $user->tasks_in_progress_count,
                'tasks_reviewing_count' => $user->tasks_reviewing_count,
                'tasks_completed_count' => $user->tasks_completed_count,
                'tasks_backlog_count' => $user->tasks_backlog_count,
                'subtasks_not_started_count' => $user->subtasks_not_started_count,
                'subtasks_in_progress_count' => $user->subtasks_in_progress_count,
                'subtasks_reviewing_count' => $user->subtasks_reviewing_count,
                'subtasks_completed_count' => $user->subtasks_completed_count,
                'subtasks_backlog_count' => $user->subtasks_backlog_count,
            ];
        });

        // Prepare the paginated response data
        return response()->json([
            'message' => $userProjectCompletion->isEmpty() ? 'No project completion found for the provided criteria.' : 'Project completion retrieved successfully.',
            'current_page' => $userProjectCompletion->currentPage(),
            'data' => $data,
            'first_page_url' => $userProjectCompletion->url(1),
            'from' => $userProjectCompletion->firstItem(),
            'last_page' => $userProjectCompletion->lastPage(),
            'last_page_url' => $userProjectCompletion->url($userProjectCompletion->lastPage()),
            'links' => $userProjectCompletion->linkCollection()->toArray(),
            'next_page_url' => $userProjectCompletion->nextPageUrl(),
            'path' => $userProjectCompletion->path(),
            'per_page' => $userProjectCompletion->perPage(),
            'prev_page_url' => $userProjectCompletion->previousPageUrl(),
            'to' => $userProjectCompletion->lastItem(),
            'total' => $userProjectCompletion->total(),
        ], 200);
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

        // Build the query with date filters and roles
        $user = User::withCount([
            'tasks as tasks_not_started_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Not started')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_in_progress_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'In progress')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_reviewing_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Reviewing')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_backlog_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Backlog')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'tasks as tasks_completed_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_progress', 'Completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_not_started_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Not started')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_in_progress_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'In progress')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_reviewing_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Reviewing')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_backlog_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Backlog')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'subtasks as subtasks_completed_count' => function ($query) use ($startDate, $endDate) {
                $query->where('project_task_subtask_progress', 'Completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
        ])
        ->where('user_id', $userId)
        ->role([$employmentType, $role])
        ->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', $this->excludedRoles);
        })
        ->first();

        // Handle the case where the user is not found
        if (! $user) {
            return response()->json([
                'message' => 'User not found or does not meet the criteria.',
            ], 404);
        }

        // Prepare the data for the response
        $data = [
            'user_id' => $user->user_id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'suffix' => $user->suffix,
            'gender' => $user->gender,
            'username' => $user->username,
            'tasks_not_started_count' => $user->tasks_not_started_count,
            'tasks_in_progress_count' => $user->tasks_in_progress_count,
            'tasks_reviewing_count' => $user->tasks_reviewing_count,
            'tasks_completed_count' => $user->tasks_completed_count,
            'tasks_backlog_count' => $user->tasks_backlog_count,
            'subtasks_not_started_count' => $user->subtasks_not_started_count,
            'subtasks_in_progress_count' => $user->subtasks_in_progress_count,
            'subtasks_reviewing_count' => $user->subtasks_reviewing_count,
            'subtasks_completed_count' => $user->subtasks_completed_count,
            'subtasks_backlog_count' => $user->subtasks_backlog_count,
        ];

        return response()->json([
            'message' => 'Project completion retrieved successfully.',
            'data' => $data,
        ], 200);
    }
}
