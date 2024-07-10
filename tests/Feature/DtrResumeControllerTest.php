<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrResumeControllerTest extends TestCase
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
     * Test the resume method for resuming a break.
     *
     * @return void
     */
    public function testResumeBreak()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::now()->subMinutes(30);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        // Create a DtrBreak entry for the user
        $breakTime = Carbon::now();
        DtrBreak::factory()->withBreakTime($breakTime)->create([
            'dtr_id' => $dtr->id,
        ]);

        $response = $this->postJson('/api/dtr/resume/' . $dtr->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Break resumed successfully.',
        ]);

        // Verify the response contains the updated DtrBreak entry
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'dtr_id',
                'break_time',
                'resume_time',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /**
     * Test the resume method when DTR record is not found.
     *
     * @return void
     */
    public function testResumeBreakDtrNotFound()
    {
        $invalidDtrId = 999; // Assumed non-existent DTR ID
        $response = $this->postJson('/api/dtr/resume/' . $invalidDtrId);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'DTR record not found.',
        ]);
    }

    /**
     * Test the resume method when no open break session is found.
     *
     * @return void
     */
    public function testResumeBreakNoOpenSession()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/resume/' . $dtr->id);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to resume break. No open break session found.',
        ]);
    }
}
