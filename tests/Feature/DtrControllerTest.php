<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrControllerTest extends TestCase
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
     * Test retrieving paginated DTR entries for an authenticated user.
     */
    public function testGetDtr(): void
    {
        // Simulate GET request to the getDtr endpoint
        $response = $this->getJson('/api/dtr');

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        [
                            'id',
                            'user_id',
                            'time_in',
                            'time_out',
                            'created_at',
                            'updated_at',
                            'breaks' => [
                                [
                                    'id',
                                    'dtr_id',
                                    'break_time',
                                    'resume_time',
                                    'created_at',
                                    'updated_at',
                                ],
                            ],
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ]);
    }

    /**
     * Test retrieving a DTR entry by ID for an authenticated user.
     */
    public function testGetDtrById(): void
    {
        // Simulate GET request to the getDtrById endpoint
        $response = $this->getJson("/api/dtr/{$this->dtr->id}");

        // Assert response status is 200
        $response->assertStatus(200);

        // Assert JSON structure
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'time_in',
                'time_out',
                'created_at',
                'updated_at',
                'breaks' => [
                    [
                        'id',
                        'dtr_id',
                        'break_time',
                        'resume_time',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test retrieving a non-existent DTR entry by ID.
     */
    public function testGetNonExistentDtrById(): void
    {
        // Simulate GET request to the getDtrById endpoint with a non-existent DTR ID
        $response = $this->getJson('/api/dtr/9999'); // Assuming 9999 is a non-existent DTR ID

        // Assert response status and structure for not found
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'DTR entry not found.',
            ]);
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
