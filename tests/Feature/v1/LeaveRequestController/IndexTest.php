<?php

namespace Tests\Feature\v1\LeaveRequestController;

use App\Models\Dtr;
use App\Models\User;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndexTest extends TestCase
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

    public function testAuthenticatedUserCanRetrievePaginatedLeaveRequestData(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.leaveRequests.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'dtr_id',
                            'dtr_absence_date',
                            'dtr_absence_reason',
                            'dtr_absence_approved_at',
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Leave requests retrieved successfully.',
        ]);
    }

    public function testAuthenticatedUserCanRetrieveEmptyPaginatedLeaveRequestData(): void
    {
        // Assert new user
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);

        // Act the response
        $response = $this->getJson(route('v1.leaveRequests.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'No leave requests found for the provided criteria.',
        ]);
    }
}
