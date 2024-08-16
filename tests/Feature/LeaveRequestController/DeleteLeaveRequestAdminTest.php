<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\User;
use Carbon\Carbon;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeleteLeaveRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $leaveRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and act as that user
        $this->user = User::factory()->create();
        $adminRole = Role::create(['name' => 'company-admin']);
        $this->user->assignRole($adminRole);
        Sanctum::actingAs($this->user);

        // Set start date to tomorrow
        $startDate = Carbon::now()->addDay()->format('Y-m-d');

        // Set end date to three days later
        $endDate = Carbon::now()->addDays(3)->format('Y-m-d');

        // Create a leave request
        $this->leaveRequest = LeaveRequestFactory::dateRange($startDate, $endDate, $this->user->user_id);
    }

    public function test_successful_destroy(): void
    {
        $response = $this->deleteJson(route('companyAdmin.leaveRequests.destroy', $this->leaveRequest->first()->dtr_id));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Leave request was successfully rejected.',
            ]);

        // Assert that the record was soft-deleted
        $this->assertSoftDeleted('dtrs', [
            'dtr_id' => $this->leaveRequest->first()->dtr_id,
        ]);
    }

    public function test_destroy_non_existent_leave_request(): void
    {
        $response = $this->deleteJson(route('companyAdmin.leaveRequests.destroy', 99999)); // Non-existent ID

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Failed to retrieve leave request.',
            ]);
    }
}
