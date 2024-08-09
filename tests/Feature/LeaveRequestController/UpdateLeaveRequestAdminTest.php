<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class UpdateLeaveRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $leaveRequests;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and act as that user
        $this->user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        Sanctum::actingAs($this->user);

        // Set start date to tomorrow
        $startDate = Carbon::now()->addDay()->format('Y-m-d');

        // Set end date to three days later
        $endDate = Carbon::now()->addDays(3)->format('Y-m-d');

        // Use the LeaveRequestFactory's dateRange method to create leave requests for the user
        $this->leaveRequests = LeaveRequestFactory::dateRange($startDate, $endDate, $this->user->user_id);
    }

    public function test_successful_update(): void
    {
        $leaveRequest = $this->leaveRequests->first();

        $response = $this->putJson(route('admin.leaveRequests.update', $leaveRequest->dtr_id));

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Leave request was successfully approved.',
                 ]);

        // Assert that the `absence_approved_at` field was updated
        $this->assertDatabaseHas('dtrs', [
            'dtr_id' => $leaveRequest->dtr_id,
            'absence_approved_at' => Carbon::now(),
        ]);
    }

    public function test_update_leave_request_not_found(): void
    {
        $response = $this->putJson(route('admin.leaveRequests.update', 99999)); // Non-existent ID

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Failed to retrieve leave request.',
                 ]);
    }

    public function test_update_with_existing_approval(): void
    {
        $leaveRequest = $this->leaveRequests->first();

        // Update the leave request to already have an approval
        $leaveRequest->update([
            'absence_approved_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->putJson(route('admin.leaveRequests.update', $leaveRequest->dtr_id));

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Failed to retrieve leave request.',
                 ]);
    }
}
