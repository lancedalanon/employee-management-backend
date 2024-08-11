<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProjectCompletionController extends Controller
{
    public function indexEmployeeFullTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $roles = ['employee', 'full-time'];
        $excludedRoles = ['admin', 'super', 'intern'];

        $projectTasksCompletion = User::with(['roles', 'projects', 'projects.tasks', 'projects.tasks.subtasks'])
            ->role($roles)
            ->whereDoesntHave('roles', function ($query) use ($excludedRoles) {
                $query->whereIn('name', $excludedRoles);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        $projectTasksCompletion->getCollection()->transform(function ($user) use ($roles) {
            $filteredRoles = $user->roles->filter(function ($role) use ($roles) {
                return in_array($role->name, $roles);
            });
    
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';
            unset($user->roles);
    
            return $user;
        });

        return Response::json([
            'message' => 'Full time employee project completions retrieved successfully.',
            'current_page' => $projectTasksCompletion->currentPage(),
            'data' => $projectTasksCompletion->items(),
            'first_page_url' => $projectTasksCompletion->url(1),
            'from' => $projectTasksCompletion->firstItem(),
            'last_page' => $projectTasksCompletion->lastPage(),
            'last_page_url' => $projectTasksCompletion->url($projectTasksCompletion->lastPage()),
            'links' => $projectTasksCompletion->linkCollection()->toArray(),
            'next_page_url' => $projectTasksCompletion->nextPageUrl(),
            'path' => $projectTasksCompletion->path(),
            'per_page' => $projectTasksCompletion->perPage(),
            'prev_page_url' => $projectTasksCompletion->previousPageUrl(),
            'to' => $projectTasksCompletion->lastItem(),
            'total' => $projectTasksCompletion->total(),
        ], 200);
    }
    
    public function showEmployeeFullTime(int $userId) 
    {

    }  

    public function indexEmployeePartTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

    }

    public function showEmployeePartTime(int $userId) 
    {

    }   

    public function indexInternFullTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

    }
    
    public function showInternFullTime(int $userId) 
    {

    }

    public function indexInternPartTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

    }

    public function showInternPartTime(int $userId) 
    {

    }
}
