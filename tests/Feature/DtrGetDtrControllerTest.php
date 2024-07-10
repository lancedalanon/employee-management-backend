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

class DtrGetDtrControllerTest extends TestCase
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
