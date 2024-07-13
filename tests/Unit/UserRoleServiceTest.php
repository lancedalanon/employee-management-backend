<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\User\UserRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userRoleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRoleService = new UserRoleService();
    }

    public function test_get_user_employment_role()
    {
        // Create roles
        $fullTimeRole = Role::create(['name' => 'full-time']);
        $partTimeRole = Role::create(['name' => 'part-time']);

        // Create a user and assign a role
        $user = User::factory()->create();
        $user->assignRole('full-time');

        // Check if the service returns the correct employment role
        $employmentRole = $this->userRoleService->getUserEmploymentRole($user);
        $this->assertEquals('full-time', $employmentRole);

        // Change the role and test again
        $user->removeRole('full-time');
        $user->assignRole('part-time');

        $employmentRole = $this->userRoleService->getUserEmploymentRole($user);
        $this->assertEquals('part-time', $employmentRole);
    }

    public function test_get_user_shift_role()
    {
        // Create roles
        $dayShiftRole = Role::create(['name' => 'day-shift']);
        $eveningShiftRole = Role::create(['name' => 'evening-shift']);

        // Create a user and assign a role
        $user = User::factory()->create();
        $user->assignRole('day-shift');

        // Check if the service returns the correct shift role
        $shiftRole = $this->userRoleService->getUserShiftRole($user);
        $this->assertEquals('day-shift', $shiftRole);

        // Change the role and test again
        $user->removeRole('day-shift');
        $user->assignRole('evening-shift');

        $shiftRole = $this->userRoleService->getUserShiftRole($user);
        $this->assertEquals('evening-shift', $shiftRole);
    }

    public function test_get_user_employment_role_returns_null_when_no_role_found()
    {
        $user = User::factory()->create();

        $employmentRole = $this->userRoleService->getUserEmploymentRole($user);
        $this->assertNull($employmentRole);
    }

    public function test_get_user_shift_role_returns_null_when_no_role_found()
    {
        $user = User::factory()->create();

        $shiftRole = $this->userRoleService->getUserShiftRole($user);
        $this->assertNull($shiftRole);
    }
}
