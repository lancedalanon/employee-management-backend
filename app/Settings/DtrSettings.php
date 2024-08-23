<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DtrSettings extends Settings
{
    public array|null $custom_shift_full_time;
    public array|null $custom_shift_part_time;

    public bool $strict_schedule;

    // Define the settings group name
    public static function group(): string
    {
        return 'dtr_schedules';
    }
}
