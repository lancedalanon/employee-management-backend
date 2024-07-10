<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DtrGetDtrControllerTest extends TestCase
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
}
