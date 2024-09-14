<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\ProjectCompletionController\IndexRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectCompletionController\ShowRequest;
use App\Services\v1\CompanyAdmin\ProjectCompletionService;

class ProjectCompletionController extends Controller
{
    protected $projectCompletionService;

    public function __construct(ProjectCompletionService $projectCompletionService)
    {
        $this->projectCompletionService = $projectCompletionService;
    }

    public function index(IndexRequest $request)
    {
        $validatedData = $request->validated();

        return $this->projectCompletionService->index($validatedData);
    }

    public function show(ShowRequest $request, int $userId)
    {
        $validatedData = $request->validated();
        
        return $this->projectCompletionService->show($validatedData, $userId);
    }
}
