<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService) 
    {
        $this->attendanceService = $attendanceService;
    }

    public function indexInternFullTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->attendanceService->indexInternFullTime($perPage, $page);
        return $response;
    }
    
    public function showInternFullTime(int $userId) 
    {
        $response = $this->attendanceService->showInternFullTime($userId);
        return $response;
    }
    
    public function indexEmployeeFullTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->attendanceService->indexEmployeeFullTime($perPage, $page);
        return $response;
    }
    
    public function showEmployeeFullTime(int $userId) 
    {
        $response = $this->attendanceService->showEmployeeFullTime($userId);
        return $response;
    }     

    public function indexInternPartTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->attendanceService->indexInternPartTime($perPage, $page);
        return $response;
    }

    public function showInternPartTime(int $userId) 
    {
        $response = $this->attendanceService->showInternPartTime($userId);
        return $response;
    }

    public function indexEmployeePartTime(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->attendanceService->indexEmployeePartTime($perPage, $page);
        return $response;
    }

    public function showEmployeePartTime(int $userId) 
    {
        $response = $this->attendanceService->showEmployeePartTime($userId);
        return $response;
    }
}
