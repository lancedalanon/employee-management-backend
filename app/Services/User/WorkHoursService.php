<?php

namespace App\Services\User;

use Carbon\Carbon;

class WorkHoursService
{
    protected $userRoleService;

    public function __construct(UserRoleService $userRoleService)
    {
        $this->userRoleService = $userRoleService;
    }

    public function evaluateTimeIn($user, $timeIn)
    {
        // Extract the user's shift role
        $shiftRole = $this->userRoleService->getUserShiftRole($user);

        // Get the current date from the timeIn timestamp
        $currentDate = $timeIn->toDateString();

        // Determine the adjusted time based on the shift-specific rules
        switch ($shiftRole) {
            case 'early-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '04:00:00', '03:00:00', '04:00:00');
                break;
            case 'late-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '22:00:00', '21:00:00', '22:00:00');
                break;
            case 'day-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '08:00:00', '07:00:00', '08:00:00');
                break;
            case 'afternoon-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '13:00:00', '12:00:00', '13:00:00');
                break;
            case 'evening-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '16:00:00', '15:00:00', '16:00:00');
                break;
            default:
                throw new \Exception('Unknown shift role');
        }

        // Return adjusted time or original timeIn if not within expected range
        return $timeIn->lt($expectedTime) ? $expectedTime : $timeIn;
    }

    public function evaluateTimeOut($user, $timeOut)
    {
        // Extract the user's shift role
        $shiftRole = $this->userRoleService->getUserShiftRole($user);

        // Get the current date from the timeIn timestamp
        $currentDate = $timeOut->toDateString();

        // Determine the adjusted time based on the shift-specific rules
        switch ($shiftRole) {
            case 'early-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '08:00:00', '07:00:00', '08:00:00');
                break;
            case 'late-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '07:00:00', '06:00:00', '07:00:00');
                break;
            case 'day-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '06:00:00', '05:00:00', '06:00:00');
                break;
            case 'afternoon-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '22:00:00', '21:00:00', '22:00:00');
                break;
            case 'evening-shift':
                $expectedTime = $this->adjustTimeForShift($currentDate, '05:00:00', '04:00:00', '05:00:00');
                break;
            default:
                throw new \Exception('Unknown shift role');
        }

        // Return adjusted time or original timeIn if not within expected range
        return $timeOut->lt($expectedTime) ? $expectedTime : $timeOut;
    }

    protected function adjustTimeForShift($currentDate, $expectedTime, $expectedStartRange, $expectedEndRange)
    {
        // Define expected start and end times
        $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedStartRange);
        $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedEndRange);

        // Check if timeIn is within the expected range minus 1 hour
        $adjustedTime = $startTime->copy()->addHour();
        return $adjustedTime->between($startTime, $endTime) ? Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedTime) : $startTime;
    }

    public function findTimeInTimeOutDifference($user, $dtr, $timeIn, $timeOut)
    {
        // Calculate the total working hours including break-resume sessions
        $totalWorkDuration = $timeIn->diffInSeconds($timeOut);

        // Subtract the duration of all breaks
        $breaks = $dtr->breaks()->get();
        foreach ($breaks as $break) {
            if ($break->resume_time) {
                $breakStart = Carbon::parse($break->break_time);
                $breakEnd = Carbon::parse($break->resume_time);
                $totalWorkDuration -= $breakStart->diffInSeconds($breakEnd);
            }
        }

        // Convert total work duration to hours
        $totalWorkHours = $totalWorkDuration / 3600;

        // Determine the required hours based on user's role
        $requiredHours = $this->getRequiredHours($user);

        // Check if total work hours meet the required hours
        return $totalWorkHours >= $requiredHours;
    }

    protected function getRequiredHours($user)
    {
        $role = $this->userRoleService->getUserEmploymentRole($user);

        switch ($role) {
            case 'full-time':
                return 8;
            case 'part-time':
                return 4;
            default:
                return 0;
        }
    }
}
