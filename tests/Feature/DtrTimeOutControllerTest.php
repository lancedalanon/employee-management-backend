<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use App\Testing\DtrTestingTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrTimeOutControllerTest extends TestCase
{
    use RefreshDatabase, DtrTestingTrait;

    /**
     * Setup method to create a user, Dtr, and DtrBreak.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUserDtrDtrBreak();
    }

    /**
     * Teardown method.
     */
    public function tearDown(): void
    {
        $this->tearDownUserDtrDtrBreak();
        parent::tearDown();
    }

    /**
     * Test successful time out.
     */
    public function testTimeOut()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Time out recorded successfully.'
            ]);
    }


    /**
     * Test DTR record not found.
     */
    public function testTimeOutDtrRecordNotFound()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/dtr/time-out/99999'); // Non-existent DTR ID

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'DTR record not found.'
            ]);
    }

    /**
     * Test time-out already recorded.
     */
    public function testTimeOutAlreadyRecorded()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $timeOut = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to time-out. Record has already been timed out.'
            ]);
    }

    /**
     * Test open break that needs to be resumed before timing out.
     */
    public function testTimeOutWithOpenBreak()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        // Create a break without a resume time
        DtrBreak::factory()->create([
            'dtr_id' => $dtr->id,
            'break_time' => Carbon::now()->subHours(2),
            'resume_time' => null,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have an open break that needs to be resumed before timing out.'
            ]);
    }

    /**
     * Test total work hours less than 8 hours.
     */
    public function testTimeOutTotalWorkHoursLessThanEight()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(7);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You need to work at least 8 hours before timing out.'
            ]);
    }
}
