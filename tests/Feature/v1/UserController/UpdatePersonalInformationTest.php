<?php

namespace Tests\Feature\v1\UserController;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePersonalInformationTest extends TestCase
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

    public function testAuthenticatedUserCanUpdateUserInformation(): void
    {
        // Arrange form data to send
        $formData = [
            'first_name' => 'Example-Name',
            'middle_name' => 'Example Middle\' Name',
            'last_name' => 'Example Last Name',
            'suffix' => 'Jr.',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePersonalInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJson(['message' => 'Personal information updated successfully.']);

        // Assert the updated user information
        $this->assertDatabaseHas('users', [
            'user_id' => $this->user->user_id,
            'first_name' => 'Example-Name',
            'middle_name' => 'Example Middle\' Name',
            'last_name' => 'Example Last Name',
            'suffix' => 'Jr.',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
        ]);
    }

    public function testAuthenticatedUserCanUpdateUserInformationIfNoChangesDone(): void
    {
        // Arrange form data to send
        $formData = [
            'first_name' => $this->user->first_name,
            'middle_name' => $this->user->middle_name,
            'last_name' => $this->user->last_name,
            'suffix' => $this->user->suffix,
            'place_of_birth' => $this->user->place_of_birth,
            'date_of_birth' => $this->user->date_of_birth,
            'gender' => $this->user->gender,
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePersonalInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJson(['message' => 'No changes detected.']);

        // Assert the updated user information
        $this->assertDatabaseHas('users', [
            'user_id' => $this->user->user_id,
            'first_name' => $this->user->first_name,
            'middle_name' => $this->user->middle_name,
            'last_name' => $this->user->last_name,
            'suffix' => $this->user->suffix,
            'place_of_birth' => $this->user->place_of_birth,
            'date_of_birth' => $this->user->date_of_birth,
            'gender' => $this->user->gender,
        ]);
    }

    public function testAuthenticatedUserFailsUpdateUserInformationIfMissingRequiredFields(): void
    {
         // Arrange form data to send
         $formData = [
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'suffix' => '',
            'place_of_birth' => '',
            'date_of_birth' => '',
            'gender' => '',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePersonalInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['first_name', 
                'last_name', 'place_of_birth', 'date_of_birth', 
                'gender']);
    }

    public function testAuthenticatedUserFailsUpdateUserInformationIfInvalidRequiredFields(): void
    {
        // Create a string with 256 characters
        $longString = str_repeat('a', 256);

        // Arrange form data to send
        $formData = [
            'first_name' => '!@#$%^&*()+=[]:"<>,./\/',
            'middle_name' => '!@#$%^&*()+=[]:"<>,./\/',
            'last_name' => '!@#$%^&*()+=[]:"<>,./\/',
            'suffix' => '!@#$%^&*()+=[]:"<>,./\/',
            'place_of_birth' => $longString,
            'date_of_birth' => Carbon::now()->addDay(1)->toDateTimeString(),
            'gender' => 'invalid-gender',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePersonalInformation'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['first_name', 'middle_name',
                'last_name', 'suffix', 'place_of_birth', 'date_of_birth', 
                'gender']);
    }
}
