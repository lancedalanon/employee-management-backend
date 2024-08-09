<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkDeleteLeaveRequestAdminTest extends TestCase
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

        // Create some leave requests
        $this->leaveRequests = Dtr::factory()->count(3)->create();
    }

    public function test_successful_bulk_destroy(): void
    {
        $dtrIds = $this->leaveRequests->pluck('dtr_id')->toArray();

        $response = $this->deleteJson(route('admin.leaveRequests.bulkDestroy'), [
            'dtr_ids' => $dtrIds,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Leave requests rejected successfully.',
                 ]);

        // Assert that the records were soft-deleted
        foreach ($dtrIds as $id) {
            $this->assertSoftDeleted('dtrs', [
                'dtr_id' => $id,
                'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function test_invalid_dtr_ids(): void
    {
        $response = $this->deleteJson(route('admin.leaveRequests.bulkDestroy'), [
            'dtr_ids' => [99999, 88888], // Non-existent IDs
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'errors' => [
                         'dtr_ids.0' => ['The selected dtr_ids.0 is invalid.'],
                         'dtr_ids.1' => ['The selected dtr_ids.1 is invalid.'],
                     ],
                 ]);

        // No records should be soft-deleted
        foreach ([99999, 88888] as $id) {
            $this->assertDatabaseMissing('dtrs', [
                'dtr_id' => $id,
                'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function test_empty_dtr_ids(): void
    {
        $response = $this->deleteJson(route('admin.leaveRequests.bulkDestroy'), [
            'dtr_ids' => [],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('dtr_ids');
    
        // Assert no records were updated
        foreach ($this->leaveRequests as $leaveRequest) {
            $this->assertDatabaseMissing('dtrs', [
                'dtr_id' => $leaveRequest->dtr_id,
                'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
