<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\LeaveRequestController\IndexRequest;
use App\Http\Requests\v1\LeaveRequestController\StoreRequest;
use App\Models\Dtr;
use App\Services\v1\LeaveRequestService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class LeaveRequestController extends Controller
{
    protected Authenticatable $user;
    protected LeaveRequestService $leaveRequestService;

    public function __construct(Authenticatable $user, LeaveRequestService $leaveRequestService)
    {
        $this->user = $user;
        $this->leaveRequestService = $leaveRequestService;
    }

    public function index(IndexRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Retrieve leave requests based on the given parameters
        return $this->leaveRequestService->getLeaveRequests($this->user, $validatedData);
    }

    public function show(int $leaveRequestId): JsonResponse
    {
        // Retrieve leave request based on the given parameters
        return  $this->leaveRequestService->getLeaveRequestById($this->user, $leaveRequestId);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Store the request
        return $this->leaveRequestService->createLeaveRequest($this->user, $validatedData);
    }

    public function destroy(int $leaveRequestId): JsonResponse
    {
        // Delete the leave request based on the given parameters
        return $this->leaveRequestService->deleteLeaveRequest($this->user, $leaveRequestId);
    }
}
