<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequest\BulkDestroyRequest;
use App\Http\Requests\LeaveRequest\BulkStoreRequest;
use App\Http\Requests\LeaveRequest\BulkUpdateRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    protected $user;
    protected $leaveRequestService;

    public function __construct(LeaveRequestService $leaveRequestService)
    {
        $this->user = Auth::user();
        $this->leaveRequestService = $leaveRequestService;
    }

    public function index(Request $request) 
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->leaveRequestService->index($perPage, $page);
        return $response;
    }
    public function show(int $leaveRequestId) 
    {
        $response = $this->leaveRequestService->show($leaveRequestId);
        return $response;
    }

    public function indexAdmin(Request $request) 
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->leaveRequestService->indexAdmin($perPage, $page);
        return $response;
    }

    public function showAdmin(int $leaveRequestId) 
    {
        $response = $this->leaveRequestService->showAdmin($leaveRequestId);
        return $response;
    }

    public function bulkStore(BulkStoreRequest $request)
    {
        $validatedData = $request->validated();
        $response = $this->leaveRequestService->bulkStore($validatedData);
        return $response;
    }

    public function update(int $leaveRequestId)
    {
        $response = $this->leaveRequestService->update($leaveRequestId);
        return $response;
    }

    public function bulkUpdate(BulkUpdateRequest $request)
    {
        $validatedData = $request->validated();
        $response = $this->leaveRequestService->bulkUpdate($validatedData);
        return $response;
    }       

    public function destroy(int $leaveRequestId)
    {
        $response = $this->leaveRequestService->destroy($leaveRequestId);
        return $response;
    }

    public function bulkDestroy(BulkDestroyRequest $request)
    {    
        $validatedData = $request->validated();
        $response = $this->leaveRequestService->bulkDestroy($validatedData);
        return $response;
    }    
}
