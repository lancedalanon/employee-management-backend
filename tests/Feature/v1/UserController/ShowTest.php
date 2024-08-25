<?php

namespace Tests\Feature\v1\UserController;

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

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanRetrieveUserInformation(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.users.show'));

        // Assert the response status and data
        $response->assertStatus(200);

        // Assert the JSON structure of the response
        $response->assertJsonStructure([
                'message',
                'data' => [
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'place_of_birth',
                    'date_of_birth',
                    'gender',
                    'username',
                    'email',
                    'recovery_email',
                    'phone_number',
                    'emergency_contact_name',
                    'emergency_contact_number',
                    'full_name',
                ]
            ]);

        // Assert the data is correct
        $response->assertJson([
            'message' => 'User information retrieved successfully.',
            'data' => [
                'first_name' => $this->user->first_name,
                'middle_name' => $this->user->middle_name,
                'last_name' => $this->user->last_name,
                'suffix' => $this->user->suffix,
                'place_of_birth' => $this->user->place_of_birth,
                'date_of_birth' => $this->user->date_of_birth,
                'gender' => $this->user->gender,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'recovery_email' => $this->user->recovery_email,
                'phone_number' => $this->user->phone_number,
                'emergency_contact_name' => $this->user->emergency_contact_name,
                'emergency_contact_number' => $this->user->emergency_contact_number,
                'full_name' => $this->user->full_name,
            ]
        ]);
    }
}
