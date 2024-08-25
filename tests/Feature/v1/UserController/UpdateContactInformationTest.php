<?php

namespace Tests\Feature\v1\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateContactInformationTest extends TestCase
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

    public function testAuthenticatedUserCanUpdateContactInformation(): void
    {
        // Arrange form data to send
        $formData = [
            'username' => 'johndoeuser1',
            'email' => 'johndoeuser1@example.com',
            'recovery_email' => 'johndoeuserrecovery1@example.com',
            'phone_number' => '0922-222-3333',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_number' => '0911-111-1111',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updateContactInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(200);

        // Assert the updated user information
        $this->assertDatabaseHas('users', [
            'user_id' => $this->user->user_id,
            'username' => 'johndoeuser1',
            'email' => 'johndoeuser1@example.com',
            'recovery_email' => 'johndoeuserrecovery1@example.com',
            'phone_number' => '0922-222-3333',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_number' => '0911-111-1111',
        ]);
    }

    public function testAuthenticatedUserCanUpdateContactInformationIfNoChangesDone(): void
    {
        // Arrange form data to send
        $formData = [
            'username' => $this->user->username,
            'email' => $this->user->email,
            'recovery_email' => $this->user->recovery_email,
            'phone_number' => $this->user->phone_number,
            'emergency_contact_name' => $this->user->emergency_contact_name,
            'emergency_contact_number' => $this->user->emergency_contact_number,
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updateContactInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJson(['message' => 'No changes detected.']);

        // Assert the updated user information
        $this->assertDatabaseHas('users', [
            'user_id' => $this->user->user_id,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'recovery_email' => $this->user->recovery_email,
            'phone_number' => $this->user->phone_number,
            'emergency_contact_name' => $this->user->emergency_contact_name,
            'emergency_contact_number' => $this->user->emergency_contact_number,
        ]);
    }

    public function testAuthenticatedUserFailsUpdateContactInformationIfMissingRequiredFields(): void
    {
        // Arrange form data to send
        $formData = [
            'username' => '',
            'email' => '',
            'phone_number' => '',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updateContactInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['username', 'email', 'phone_number']);
    }

    public function testAuthenticatedUserFailsUpdateContractInformationIfInvalidRequiredFields(): void
    {
        // Create a string with 256 characters
        $longString = str_repeat('a', 256);

        // Arrange form data to send
        $formData = [
            'username' => $longString,
            'email' => $longString,
            'recovery_email' => $longString,
            'phone_number' => $longString,
            'emergency_contact_name' => $longString,
            'emergency_contact_number' => $longString,
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updateContactInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['username', 
                'email', 'recovery_email', 'phone_number', 
                'emergency_contact_name', 'emergency_contact_number']);
    }
}
