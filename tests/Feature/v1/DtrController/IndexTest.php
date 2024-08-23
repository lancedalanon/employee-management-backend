<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $dtr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles()->create();
        Sanctum::actingAs($this->user);

        // Create a sample DTR record for the user with a time-in event
        $this->dtr = Dtr::factory()->withTimeOut()->count(10)->create(['user_id' => $this->user->user_id]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->dtr = null;

        parent::tearDown();
    }

    // Main function
    public function testAuthenticatedUserCanRetrievePaginatedDtrData(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.dtrs.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'dtr_id', 
                            'dtr_time_in', 
                            'dtr_time_out',
                            'dtr_end_of_the_day_report', 
                            'dtr_is_overtime'
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'DTR records retrieved successfully.',
        ]);

        // Assert specific data within the pagination structure
        $response->assertJson([
            'data' => [
                'current_page' => 1,
                'per_page' => 25,
                'total' => 10,
            ],
        ]);
    }

    public function testAuthenticatedUserCanRetrieveEmptyPaginatedDtrData(): void
    {
        // Arrange new user to have empty data for dtr
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);
        
        // Act the response
        $response = $this->getJson(route('v1.dtrs.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'No DTR records found for the provided criteria.',
        ]);
    }
}
