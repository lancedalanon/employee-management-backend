<?php

namespace Tests\Feature\v1\LeaveRequestController;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;

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
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanAddLeaveRequest(): void
    {
        // Arrange form data
        $formData = [
            'dtr_absence_date' => Carbon::now()->format('Y-m-d'),
            'dtr_absence_reason' => 'This is a random reason for leave request.',
        ];

        // Act the response
        $response = $this->postJson(route('v1.leaveRequests.store'), $formData);

        // Assert the response status and data
        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Leave request created successfully.',
            ]);
        
        // Assert database has the requested leave request
        $this->assertDatabaseHas('dtrs', [
            'user_id' => $this->user->user_id,
            'dtr_absence_date' => Carbon::now()->format('Y-m-d'),
            'dtr_absence_reason' => 'This is a random reason for leave request.',
        ]);
    }

    public function testUnauthenticatedUserFailsToAddLeaveRequestIfThereAreMissingFields(): void
    {
        // Arrange form data
        $formData = [
            'dtr_absence_date' => '',
            'dtr_absence_reason' => '',
        ];

        // Act the response
        $response = $this->postJson(route('v1.leaveRequests.store'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dtr_absence_date', 'dtr_absence_reason']);
    }

    public function testUnauthenticatedUserFailsToAddLeaveRequestIfThereAreInvalidFields(): void
    {
        // Arrange variable with 256 characters
        $longString = str_repeat('a', 256);

        // Arrange form data
        $formData = [
            'dtr_absence_date' => 'invalid-date',
            'dtr_absence_reason' => $longString,
        ];

        // Act the response
        $response = $this->postJson(route('v1.leaveRequests.store'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dtr_absence_date', 'dtr_absence_reason']);
    }
}
