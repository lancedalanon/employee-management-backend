<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkUpdateLeaveRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $leaveRequests;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and act as that user
        $this->user = User::factory()->create();
        $adminRole = Role::create(['name' => 'company-admin']);
        $this->user->assignRole($adminRole);
        Sanctum::actingAs($this->user);

        // Create some leave requests
        $this->leaveRequests = Dtr::factory()->count(3)->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_successful_bulk_update(): void
    {
        $dtrIds = $this->leaveRequests->pluck('dtr_id')->toArray();

        $response = $this->patchJson(route('companyAdmin.leaveRequests.bulkUpdate'), [
            'dtr_ids' => $dtrIds,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Leave requests updated successfully',
            ]);
    }

    public function test_invalid_dtr_ids(): void
    {
        $response = $this->patchJson(route('companyAdmin.leaveRequests.bulkUpdate'), [
            'dtr_ids' => [99999, 88888], // Non-existent IDs
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'dtr_ids.0' => ['The selected dtr_ids.0 is invalid.'],
                    'dtr_ids.1' => ['The selected dtr_ids.1 is invalid.'],
                ],
            ]);
    }

    public function test_empty_dtr_ids(): void
    {
        $response = $this->patchJson(route('companyAdmin.leaveRequests.bulkUpdate'), [
            'dtr_ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('dtr_ids');
    }
}
