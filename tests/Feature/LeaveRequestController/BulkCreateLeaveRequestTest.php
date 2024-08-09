<?php

namespace Tests\Feature\LeaveRequestController;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulkCreateLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_successful_bulk_store(): void
    {
        $startDate = Carbon::now()->addDay()->format('Y-m-d');
        $endDate = Carbon::now()->addDays(3)->format('Y-m-d');
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => $startDate,
            'absence_end_date' => $endDate,
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Leave requests stored successfully',
                 ]);
    }

    public function test_missing_absence_start_date(): void
    {
        $response = $this->
        postJson(route('leaveRequests.bulkStore'), [
            'absence_end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_start_date');
    }

    public function test_missing_absence_end_date(): void
    {
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_end_date');
    }

    public function test_absence_end_date_before_start_date(): void
    {
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'absence_end_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_end_date');
    }

    public function test_invalid_absence_start_date_format(): void
    {
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => 'invalid-date',
            'absence_end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_start_date');
    }

    public function test_invalid_absence_end_date_format(): void
    {
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'absence_end_date' => 'invalid-date',
            'absence_reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_end_date');
    }

    public function test_absence_reason_too_long(): void
    {
        $response = $this->postJson(route('leaveRequests.bulkStore'), [
            'absence_start_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'absence_end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'absence_reason' => str_repeat('A', 256), // Exceeds max length
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('absence_reason');
    }
}
