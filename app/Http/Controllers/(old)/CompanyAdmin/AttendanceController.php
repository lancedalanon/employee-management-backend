<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Attendance\IndexAttendanceRequest;
use App\Http\Requests\Admin\Attendance\ShowAttendanceRequest;
use App\Services\CompanyAdmin\AttendanceService;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(IndexAttendanceRequest $request)
    {
        $validatedData = $request->validated();
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $response = $this->attendanceService->index($validatedData, $perPage, $page, $startDate, $endDate);

        return $response;
    }

    public function show(ShowAttendanceRequest $request, int $userId)
    {
        $validatedData = $request->validated();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $response = $this->attendanceService->show($validatedData, $userId, $startDate, $endDate);

        return $response;
    }
}
