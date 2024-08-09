<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\User;
use Carbon\Carbon;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetLeaveRequestByIdAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $leaveRequests;

    protected function setUp(): void
    {
        parent::setUp();

        // Set start date to tomorrow
        $startDate = Carbon::now()->addDay()->format('Y-m-d');

        // Set end date to three days later
        $endDate = Carbon::now()->addDays(3)->format('Y-m-d');

        // Create a user
        $this->user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);

        // Use the LeaveRequestFactory's dateRange method to create leave requests for the user
        $this->leaveRequests = LeaveRequestFactory::dateRange($startDate, $endDate, $this->user->user_id);

        // Act as the created user using Sanctum
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_successful_retrieval_of_leave_request(): void
    {
        $response = $this->getJson(route('admin.leaveRequests.showAdmin', ['leaveRequestId' => $this->leaveRequests->first()->dtr_id]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'dtr_id',
                    'user_id',
                    'absence_date',
                    'absence_reason',
                    'absence_approved_at',
                ],
            ]);
    }

    public function test_leave_request_not_found(): void
    {
        $response = $this->getJson(route('admin.leaveRequests.showAdmin', ['leaveRequestId' => 99999]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Leave request not found.'
            ]);
    }
}
