<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrBreakControllerTest extends TestCase
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
     * Test the break method for starting a break.
     *
     * @return void
     */
    public function testStartBreak()
    {
        // Add new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/break/' . $dtr->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Break started successfully.',
        ]);

        // Verify the response contains the latest DtrBreak entry
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'dtr_id',
                'break_time',
                'created_at',
                'updated_at',
            ],
        ]);

        // Check if the break was recorded in the database
        $this->assertDatabaseHas('dtr_breaks', [
            'dtr_id' => $dtr->id,
        ]);
    }

    /**
     * Test the break method when there is an open break session.
     *
     * @return void
     */
    public function testStartBreakWithOpenBreakSession()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $timeIn = Carbon::now()->subHour();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $break = Carbon::now()->subMinutes(30);
        DtrBreak::factory()->withBreakTime($break)->create([
            'dtr_id' => $dtr->id,
        ]);

        $response = $this->postJson('/api/dtr/break/' . $dtr->id);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to start break. You have an open break session.',
        ]);
    }

    /**
     * Test the break method when DTR record is not found.
     *
     * @return void
     */
    public function testStartBreakDtrNotFound()
    {
        $invalidDtrId = 999; // Assumed non-existent DTR ID
        $response = $this->postJson('/api/dtr/break/' . $invalidDtrId);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'DTR record not found.',
        ]);
    }
}
