<?php

namespace Tests\Feature\v1\LeaveRequestController;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $leaveRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles()->create();
        Sanctum::actingAs($this->user);

        // Create a sample leave request for the user
        $this->leaveRequest = Dtr::factory()->withLeaveRequest()->create(['user_id' => $this->user->user_id]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->leaveRequest = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanRetrieveLeaveRequestDataById(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.leaveRequests.show', $this->leaveRequest->dtr_id));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'dtr_id',
                    'dtr_absence_date',
                    'dtr_absence_reason',
                    'dtr_absence_approved_at',
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Leave request retrieved successfully.',
        ]);
    }

    public function testAuthenticatedUserFailsToRetrieveLeaveRequestDataByIdIfLeaveRequestDoesNotExist(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.leaveRequests.show', 99999));

        // Assert the response status code and data structure
        $response->assertStatus(404)
            ->assertJsonFragment([
            'message' => 'Leave request not found.',
        ]);
    }
}
