<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

        // Create a Dtr entry for the user
        $this->dtr = Dtr::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create a DtrBreak entry associated with the Dtr entry
        $this->dtrBreak = DtrBreak::factory()->create([
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

        // Clean up code after each test if needed
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
}
