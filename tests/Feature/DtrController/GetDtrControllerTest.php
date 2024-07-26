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

class GetDtrControllerTest extends TestCase
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
    public function test_get_dtr(): void
    {
        // Simulate GET request to the getDtr endpoint
        $response = $this->getJson(route('dtrs.index'));

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'current_page',
                'data' => [
                    [
                        'dtr_id',
                        'user_id',
                        'time_in',
                        'time_out',
                        'created_at',
                        'updated_at',
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
            ]);
    }

    /**
     * Test retrieving a DTR entry by ID for an authenticated user.
     */
    public function test_get_dtr_by_id(): void
    {
        // Simulate GET request to the getDtrById endpoint
        $response = $this->getJson(route('dtrs.show', [
            'dtr' => $this->dtr->dtr_id,
        ]));

        // Assert response status is 200
        $response->assertStatus(200);

        // Assert JSON structure
        $response->assertJsonStructure([
            'message',
            'data' => [
                'dtr_id',
                'user_id',
                'time_in',
                'time_out',
                'created_at',
                'updated_at',
                'breaks' => [
                    [
                        'dtr_break_id',
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
    public function test_get_non_existent_dtr_by_id(): void
    {
        // Simulate GET request to the getDtrById endpoint with a non-existent DTR ID
        $response = $this->getJson(route('dtrs.show', [
            'dtr' => 'invalidId',
        ]));

        // Assert response status and structure for not found
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'DTR entry not found.',
            ]);
    }
}
