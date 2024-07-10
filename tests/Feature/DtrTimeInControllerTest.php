<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrTimeInControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $dtr;
    private $dtrBreak;

    /**
     * Setup method to create a user, Dtr, and DtrBreak.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::parse('2023-07-01 08:00:00');
        $breakTime = Carbon::parse('2023-07-01 12:00:00');
        $resumeTime = Carbon::parse('2023-07-01 13:00:00');
        $timeOut = Carbon::parse('2023-07-01 18:00:00');

        // Create a Dtr with a specific time_in
        $this->dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $this->user->id,
        ]);

        // Create a DtrBreak with specific break_time and resume_time
        $this->dtrBreak = DtrBreak::factory()
            ->withBreakTime($breakTime)
            ->withResumeTime($resumeTime)
            ->create([
                'dtr_id' => $this->dtr->id,
            ]);

        // Authenticate the user
        Sanctum::actingAs($this->user);
    }

    /**
     * Teardown method.
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test successful time in.
     */
    public function testTimeIn()
    {
        $response = $this->postJson('/api/dtr/time-in');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Time in recorded successfully.'
            ]);

        $this->assertDatabaseHas('dtrs', [
            'user_id' => $this->user->id,
            'time_out' => null
        ]);
    }

    /**
     * Test open time record needs to be closed before timing in again.
     */
    public function testTimeInOpenTimeRecordExists()
    {
        // Create an existing DTR record without a time_out
        Dtr::factory()->create([
            'user_id' => $this->user->id,
            'time_in' => Carbon::now()->subHours(2),
            'time_out' => null
        ]);

        $response = $this->postJson('/api/dtr/time-in');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have an open time record that needs to be closed before timing in again.'
            ]);
    }
}
