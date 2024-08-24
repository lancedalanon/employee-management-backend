<?php

namespace App\Services\v1;

use App\Settings\DtrSettings;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;

class EvaluateScheduleService
{
    protected ?string $employmentType = null;
    protected ?string $shiftType = null;

    public function evaluateSchedule(Authenticatable $user)
    {
        // Check and set employment type and shift type
        $this->checkEmploymentType($user);
        $this->checkShiftType($user);
    
        // Check if employment type and shift type are found
        if (!$this->employmentType || !$this->shiftType) {
            return false;
        }
    
        // Load the DTR settings
        $settings = app(DtrSettings::class);
    
        // Get the current time
        $now = Carbon::now();
    
        // Determine which schedule to use based on the strict_schedule setting
        if ($settings->strict_schedule) {
            // Use the default schedule from the config
            $schedules = config('constants.dtr_schedules');
    
            // Find the matching schedule based on shift type
            $schedule = $schedules[$this->shiftType][$this->employmentType] ?? null;
    
            // Check if the schedule is found
            if (!$schedule) {
                return false;
            }
        } else {
            // Use custom shift schedules from settings
            $schedule = $this->employmentType === 'full_time' ? $settings->custom_shift_full_time : $settings->custom_shift_part_time;
    
            // Check if the schedule is found
            if (!$schedule) {
                return false;
            }
        }
    
        // Parse the start and end times
        $startTime = Carbon::parse($schedule['start_time']);
        $endTime = Carbon::parse($schedule['end_time']);
    
        // If end time is earlier than start time, it means it's past midnight
        if ($endTime < $startTime) {
            $endTime->addDay();
        }
    
        // Apply a 30-minute grace period to both the start and end times
        $startTimeWithGrace = $startTime->copy()->subMinutes(30);
        $endTimeWithGrace = $endTime->copy()->addMinutes(30);
    
        // Convert times to their string representations
        $nowString = $now->toDateTimeString();
        $startTimeWithGraceString = $startTimeWithGrace->toDateTimeString();
        $endTimeWithGraceString = $endTimeWithGrace->toDateTimeString();
    
        // Check if the current time is within the schedule range including grace periods
        return $nowString >= $startTimeWithGraceString && $nowString <= $endTimeWithGraceString;
    }

    public function isTimeOutLate(Authenticatable $user, Carbon $timeIn): bool
    {
        // Check and set employment type and shift type
        $this->checkEmploymentType($user);
        $this->checkShiftType($user);
    
        // Check if employment type and shift type are found
        if (!$this->employmentType || !$this->shiftType) {
            return false;
        }
    
        // Load the DTR settings
        $settings = app(DtrSettings::class);
    
        // Determine which schedule to use based on the strict_schedule setting
        if ($settings->strict_schedule) {
            // Use the default schedule from the config
            $schedules = config('constants.dtr_schedules');
    
            // Find the matching schedule based on shift type
            $schedule = $schedules[$this->shiftType][$this->employmentType] ?? null;
    
            // Check if the schedule is found
            if (!$schedule) {
                return false;
            }
        } else {
            // Use custom shift schedules from settings
            $schedule = $this->employmentType === 'full_time' ? $settings->custom_shift_full_time : $settings->custom_shift_part_time;
    
            // Check if the schedule is found
            if (!$schedule) {
                return false;
            }
        }

        // Parse the start and end times
        $startTime = Carbon::parse($schedule['start_time']);
        $endTime = Carbon::parse($schedule['end_time']);

        // If end time is earlier than start time, it means it's past midnight
        if ($endTime < $startTime) {
            $startTime->addDay();
        }
    
        return $startTime > $timeIn;
    }

    private function checkEmploymentType(Authenticatable $user): void
    {   
        // Check if the user has the 'full_time' role
        if ($user->hasAnyRole('full_time')) {
            $this->employmentType = 'full_time';
        }
        // Check if the user has the 'part_time' role
        elseif ($user->hasAnyRole('part_time')) {
            $this->employmentType = 'part_time';
        } else {
            // Handle cases where the user has neither role, if needed
            $this->employmentType = null; // or throw an exception, etc.
        }
    }

    private function checkShiftType(Authenticatable $user): void
    {   
        // Check if the user has the 'early_shift' role
        if ($user->hasAnyRole('early_shift')) {
            $this->shiftType = 'early_shift';
        }
        // Check if the user has the 'day_shift' role
        elseif ($user->hasAnyRole('day_shift')) {
            $this->shiftType = 'day_shift';
        }
        // Check if the user has the 'afternoon_shift' role
        elseif ($user->hasAnyRole('afternoon_shift')) {
            $this->shiftType = 'afternoon_shift';
        }
        // Check if the user has the 'night_shift' role
        elseif ($user->hasAnyRole('night_shift')) {
            $this->shiftType = 'night_shift';
        }
        // Check if the user has the 'evening_shift' role
        elseif ($user->hasAnyRole('evening_shift')) {
            $this->shiftType = 'evening_shift';
        } 
        else {
            // Handle cases where the user has neither role, if needed
            $this->shiftType = null; 
        }
    }
}
