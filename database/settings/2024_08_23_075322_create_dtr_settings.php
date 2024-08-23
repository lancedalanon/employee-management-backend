<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Adding the custom shift schedule for full_time and part_time
        $this->migrator->add('dtr_schedules.custom_shift_full_time', [
            'start_time' => null,
            'end_time' => null,
        ]);

        $this->migrator->add('dtr_schedules.custom_shift_part_time', [
            'start_time' => null,
            'end_time' => null,
        ]);

        // Adding the strict_schedule boolean setting
        $this->migrator->add('dtr_schedules.strict_schedule', true); // Default to true
    }
};
