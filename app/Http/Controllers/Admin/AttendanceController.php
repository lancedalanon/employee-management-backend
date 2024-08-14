<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Attendace\IndexAttendanceRequest;
use App\Http\Requests\Admin\Attendace\ShowAttendanceRequest;
use App\Services\Admin\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService) 
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(IndexAttendanceRequest $request)
    {
        // Retrieve validated data
        $validatedData = $request->validated();
        
        // Get the validated query parameters for filtering
        $employmentStatus = $validatedData['employment_status']; // e.g., 'full-time' or 'part-time'
        $personnel = $validatedData['personnel']; // e.g., 'employee' or 'intern'
        
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Call the appropriate service method based on the parameters
        $response = $this->attendanceService->index($employmentStatus, $personnel, $perPage, $page);
        return $response;
    }
    
    public function show(ShowAttendanceRequest $request, int $userId)
    {
        // Retrieve validated data
        $validatedData = $request->validated();
        
        // Get the validated query parameters for filtering
        $employmentStatus = $validatedData['employment_status']; // e.g., 'full-time' or 'part-time'
        $personnel = $validatedData['personnel']; // e.g., 'employee' or 'intern'
    
        // Call the appropriate service method based on the parameters
        $response = $this->attendanceService->show($employmentStatus, $personnel, $userId);
        return $response;
    }    
}
