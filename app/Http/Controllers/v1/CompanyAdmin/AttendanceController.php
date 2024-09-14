<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\AttendanceController\IndexAttendanceRequest;
use App\Http\Requests\v1\CompanyAdmin\AttendanceController\ShowAttendanceRequest;
use App\Http\Services\v1\CompanyAdmin\AttendanceService;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(IndexAttendanceRequest $request)
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Call the attendance service to retrieve attendance records
        return $this->attendanceService->index($validatedData);
    }

    public function show(ShowAttendanceRequest $request, int $userId)
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Call the attendance service to retrieve attendance records for a specific user
        return $this->attendanceService->show($validatedData, $userId);
    }
}
