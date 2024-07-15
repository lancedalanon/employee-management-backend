<?php

namespace App\Testing;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

trait DtrTestingTrait
{
    protected $user;
    protected $dtr;
    protected $dtrBreak;

    public function setUpUserDtrDtrBreak(): void
    {
        // Create a user
        $this->user = User::factory()->create();

        // Create roles
        $studentRole = Role::create(['name' => 'student']);
        $fullTimeRole = Role::create(['name' => 'full-time']);
        $dayShiftRole = Role::create(['name' => 'day-shift']);

        // Assign roles to the user
        $this->user->assignRole($fullTimeRole);
        $this->user->assignRole($dayShiftRole);
        $this->user->assignRole($studentRole);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::parse('2023-07-01 08:00:00');
        $breakTime = Carbon::parse('2023-07-01 12:00:00');
        $resumeTime = Carbon::parse('2023-07-01 13:00:00');
        $timeOut = Carbon::parse('2023-07-01 18:00:00');

        // Create a Dtr with a specific time_in
        $this->dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $this->user->user_id,
        ]);

        // Create a DtrBreak with specific break_time and resume_time
        $this->dtrBreak = DtrBreak::factory()
            ->withBreakTime($breakTime)
            ->withResumeTime($resumeTime)
            ->create([
                'dtr_id' => $this->dtr->dtr_id,
            ]);

        // Authenticate the user
        Sanctum::actingAs($this->user);
    }

    public function tearDownUserDtrDtrBreak(): void
    {
        // Optionally add any teardown logic specific to user, Dtr, DtrBreak cleanup
    }
}
