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

    /**
     * Evaluate the time in based on the user's shift and handle shift-specific adjustments.
     *
     * @param \App\Models\User $user The user object
     * @param \Carbon\Carbon $timeIn The time the user logged in
     * @return \Carbon\Carbon The adjusted time based on shift or current time if not within expected range
     *
     * @throws \Exception When an unknown shift role is encountered
     */
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

    /**
     * Adjusts the time-in based on the specified expected time and range.
     *
     * @param string $currentDate The current date in 'Y-m-d' format
     * @param string $expectedTime The expected time in 'H:i:s' format
     * @param string $expectedStartRange The expected start range in 'H:i:s' format
     * @param string $expectedEndRange The expected end range in 'H:i:s' format
     * @return \Carbon\Carbon The adjusted time based on shift and expected range
     */
    protected function adjustTimeForShift($currentDate, $expectedTime, $expectedStartRange, $expectedEndRange)
    {
        // Define expected start and end times
        $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedStartRange);
        $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedEndRange);

        // Check if timeIn is within the expected range minus 1 hour
        $adjustedTime = $startTime->copy()->addHour();
        return $adjustedTime->between($startTime, $endTime) ? Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $expectedTime) : $startTime;
    }
}
