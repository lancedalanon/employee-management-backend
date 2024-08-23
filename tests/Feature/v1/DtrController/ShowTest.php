<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowTest extends TestCase
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

    public function testAuthenticatedUserCanRetrieveDtrDataById(): void
    {
        // Arrange the ID of the first Dtr record
        $dtrId = $this->dtr->first()->dtr_id;

        // Act the response
        $response = $this->getJson(route('v1.dtrs.show', ['dtrId' => $dtrId]));

        // Assert the response status and data
        $response->assertStatus(200);

        // Assert the JSON structure of the response
        $response->assertJsonStructure([
                'message',
                'data' => [
                    'dtr_id', 
                    'dtr_time_in', 
                    'dtr_time_out',
                    'dtr_end_of_the_day_report', 
                    'dtr_is_overtime',
                ],
            ]);

        // Assert the data is correct
        $response->assertJson([
            'message' => 'DTR record retrieved successfully.',
            'data' => [
                'dtr_id' => $this->dtr->first()->dtr_id, 
                'dtr_time_in' => $this->dtr->first()->dtr_time_in, 
                'dtr_time_out' => $this->dtr->first()->dtr_time_out,
                'dtr_end_of_the_day_report' => $this->dtr->first()->dtr_end_of_the_day_report, 
                'dtr_is_overtime' => $this->dtr->first()->dtr_is_overtime,
            ],
        ]);
    }

    public function testAuthenticatedUserFailsToRetrieveDtrDataByIdIfDtrIdDoesNotExist(): void
    {
        // Arrange the ID of the first Dtr record
        $dtrId = 99999;

        // Act the response
        $response = $this->getJson(route('v1.dtrs.show', ['dtrId' => $dtrId]));

        // Assert the response status and data
        $response->assertStatus(404);

        // Assert the message is correct
        $response->assertJson([
            'message' => 'DTR record not found.',
        ]);
    }
}
