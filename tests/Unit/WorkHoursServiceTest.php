<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Models\User;
use App\Services\User\UserRoleService;
use App\Services\User\WorkHoursService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class WorkHoursServiceTest extends TestCase
{
    protected $workHoursService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock UserRoleService dependency
        $userRoleService = Mockery::mock(UserRoleService::class);

        // Define the mock behavior for getUserShiftRole method
        $userRoleService->shouldReceive('getUserShiftRole')
            ->with(Mockery::type(User::class))
            ->andReturnUsing(function ($user) {
                // Simulate different shift roles based on user attributes or IDs
                switch ($user->id) {
                    case 1:
                        return 'early-shift';
                    case 2:
                        return 'late-shift';
                    case 3:
                        return 'day-shift';
                    case 4:
                        return 'afternoon-shift';
                    case 5:
                        return 'evening-shift';
                    default:
                        return 'unknown-shift';
                }
            });

        // Define the mock behavior for getUserEmploymentRole method
        $userRoleService->shouldReceive('getUserEmploymentRole')
            ->with(Mockery::type(User::class))
            ->andReturnUsing(function ($user) {
                // Simulate different employment roles based on user attributes or IDs
                switch ($user->id) {
                    case 1:
                        return 'full-time';
                    case 2:
                        return 'part-time';
                    default:
                        return 'unknown-role';
                }
            });

        // Instantiate WorkHoursService with mocked UserRoleService
        $this->workHoursService = new WorkHoursService($userRoleService);
    }

    public function test_it_adjusts_time_in_for_early_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 1; // Set user ID or attributes as needed
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 03:30:00'); // Example time in

        // Act
        $adjustedTime = $this->workHoursService->evaluateTimeIn($user, $timeIn);

        // Assert
        $this->assertEquals('2024-07-14 04:00:00', $adjustedTime->format('Y-m-d H:i:s'));
    }

    public function test_it_adjusts_time_in_for_late_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 2; // Set user ID or attributes as needed
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 21:30:00'); // Example time in

        // Act
        $adjustedTime = $this->workHoursService->evaluateTimeIn($user, $timeIn);

        // Assert
        $this->assertEquals('2024-07-14 22:00:00', $adjustedTime->format('Y-m-d H:i:s'));
    }

    public function test_it_adjusts_time_in_for_day_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 3; // Set user ID or attributes as needed
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 07:30:00'); // Example time in

        // Act
        $adjustedTime = $this->workHoursService->evaluateTimeIn($user, $timeIn);

        // Assert
        $this->assertEquals('2024-07-14 08:00:00', $adjustedTime->format('Y-m-d H:i:s'));
    }

    public function test_it_adjusts_time_in_for_afternoon_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 4; // Set user ID or attributes as needed
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 12:30:00'); // Example time in

        // Act
        $adjustedTime = $this->workHoursService->evaluateTimeIn($user, $timeIn);

        // Assert
        $this->assertEquals('2024-07-14 13:00:00', $adjustedTime->format('Y-m-d H:i:s'));
    }

    public function test_it_adjusts_time_in_for_evening_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 5; // Set user ID or attributes as needed
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 15:30:00'); // Example time in

        // Act
        $adjustedTime = $this->workHoursService->evaluateTimeIn($user, $timeIn);

        // Assert
        $this->assertEquals('2024-07-14 16:00:00', $adjustedTime->format('Y-m-d H:i:s'));
    }

    public function test_it_throws_exception_for_unknown_shift()
    {
        // Arrange
        $user = new User(); // Create a new user (adjust as per your user model)
        $user->id = 999; // Set user ID or attributes as needed for an unknown scenario
        $timeIn = Carbon::now(); // Example time in

        // Expect exception
        $this->expectException(\Exception::class);

        // Act
        $this->workHoursService->evaluateTimeIn($user, $timeIn);
    }

    public function test_it_returns_true_for_full_time_with_enough_hours()
    {
        // Arrange
        $user = new User(); // Create a new user (can be adjusted based on your user model)
        $dtr = new Dtr(); // Create a new DTR instance
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 08:00:00'); // Example time in
        $timeOut = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 17:00:00'); // Example time out

        // Act
        $result = $this->workHoursService->findTimeInTimeOutDifference($user, $dtr, $timeIn, $timeOut);

        // Assert
        $this->assertTrue($result);
    }

    public function test_it_returns_true_for_part_time_with_enough_hours()
    {
        // Arrange
        $user = new User(); // Create a new user (can be adjusted based on your user model)
        $dtr = new Dtr(); // Create a new DTR instance
        $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 08:00:00'); // Example time in
        $timeOut = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-14 12:00:00'); // Example time out

        // Act
        $result = $this->workHoursService->findTimeInTimeOutDifference($user, $dtr, $timeIn, $timeOut);

        // Assert
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close(); // Close mockery to avoid memory leaks
        parent::tearDown();
    }
}
