<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectCompletion\IndexProjectCompletionRequest;
use App\Http\Requests\Admin\ProjectCompletion\ShowProjectCompletionRequest;
use App\Services\Admin\ProjectCompletionService;

class ProjectCompletionController extends Controller
{
    protected $excludedRoles;

    protected $roles;

    protected $projectCompletionService;

    public function __construct(ProjectCompletionService $projectCompletionService)
    {
        $this->excludedRoles = ['admin', 'super', 'intern'];
        $this->roles = ['intern', 'employee'];
        $this->projectCompletionService = $projectCompletionService;
    }

    public function index(IndexProjectCompletionRequest $request)
    {
        $validatedData = $request->validated();
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $response = $this->projectCompletionService->index($validatedData, $perPage, $page, $startDate, $endDate);
        return $response;
    }

    public function show(ShowProjectCompletionRequest $request, int $userId)
    {
        $validatedData = $request->validated();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $response = $this->projectCompletionService->show($validatedData, $userId, $startDate, $endDate);
        return $response;
    }
}
