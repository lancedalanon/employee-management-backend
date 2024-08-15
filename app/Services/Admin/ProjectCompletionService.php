<?php

namespace App\Services\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ProjectCompletionService extends Controller
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

            // Build the query with date filters
            $userProjectCompletion = User::withCount([
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
                'subtasks as subtasks_completed_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('project_task_subtask_progress', 'Completed')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
                ->role($employmentStatus)
                ->role($personnel)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', $this->excludedRoles);
                })
                ->paginate($perPage, ['*'], 'page', $page);

            // Prepare the data for the response
            $data = $userProjectCompletion->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'suffix' => $user->suffix,
                    'place_of_birth' => $user->place_of_birth,
                    'date_of_birth' => $user->date_of_birth,
                    'gender' => $user->gender,
                    'username' => $user->username,
                    'email' => $user->email,
                    'recovery_email' => $user->recovery_email,
                    'phone_number' => $user->phone_number,
                    'emergency_contact_name' => $user->emergency_contact_name,
                    'emergency_contact_number' => $user->emergency_contact_number,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
                    'tasks_not_started_count' => $user->tasks_not_started_count,
                    'tasks_in_progress_count' => $user->tasks_in_progress_count,
                    'tasks_reviewing_count' => $user->tasks_reviewing_count,
                    'tasks_completed_count' => $user->tasks_completed_count,
                    'subtasks_not_started_count' => $user->subtasks_not_started_count,
                    'subtasks_in_progress_count' => $user->subtasks_in_progress_count,
                    'subtasks_reviewing_count' => $user->subtasks_reviewing_count,
                    'subtasks_completed_count' => $user->subtasks_completed_count,
                ];
            });

            return Response::json([
                'message' => 'User attendances retrieved successfully.',
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return Response::json([
                'message' => 'An error occurred while retrieving project completions.',
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
                'subtasks as subtasks_completed_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('project_task_subtask_progress', 'Completed')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
                ->where('user_id', $userId)
                ->role([$employmentStatus, $personnel])
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', $this->excludedRoles);
                })
                ->first();

            // Handle the case where the user is not found
            if (! $user) {
                return Response::json([
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
                'place_of_birth' => $user->place_of_birth,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'username' => $user->username,
                'email' => $user->email,
                'recovery_email' => $user->recovery_email,
                'phone_number' => $user->phone_number,
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_number' => $user->emergency_contact_number,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'deleted_at' => $user->deleted_at,
                'tasks_not_started_count' => $user->tasks_not_started_count,
                'tasks_in_progress_count' => $user->tasks_in_progress_count,
                'tasks_reviewing_count' => $user->tasks_reviewing_count,
                'tasks_completed_count' => $user->tasks_completed_count,
                'subtasks_not_started_count' => $user->subtasks_not_started_count,
                'subtasks_in_progress_count' => $user->subtasks_in_progress_count,
                'subtasks_reviewing_count' => $user->subtasks_reviewing_count,
                'subtasks_completed_count' => $user->subtasks_completed_count,
            ];

            return Response::json([
                'message' => 'User project completion retrieved successfully.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return Response::json([
                'message' => 'An error occurred while retrieving project completions.',
            ], 500);
        }
    }
}
