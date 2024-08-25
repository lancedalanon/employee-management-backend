<?php

namespace Tests\Feature\v1\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
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

    public function testAuthenticatedUserCanUpdatePassword(): void
    {
        // Arrange to create a new password and set the old password to match the existing password
        $newPassword = 'new-password';
        $oldPassword = 'current-password';
        $this->user->password = Hash::make($oldPassword); // Ensure the old password is correctly hashed
        $this->user->save(); // Save the user with the old password
    
        // Form data for password update
        $formData = [
            'old_password' => $oldPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ];
    
        // Act to send the request to update the password
        $response = $this->putJson(route('v1.users.updatePassword'), $formData);
    
        // Assert the response status is 200
        $response->assertStatus(200);
    
        // Assert that the old password is not present in the database
        $this->assertDatabaseMissing('users', [
            'user_id' => $this->user->user_id,
            // Old password hashed value should not be present
            'password' => $this->user->password
        ]);
    
        // Reload the user from the database
        $this->user->refresh();
    
        // Assert that the new password can be authenticated
        $this->assertTrue(Hash::check($newPassword, $this->user->password), 'New password does not match.');
    }    

    public function testAuthenticatedUserFailsUpdatePasswordIfMissingRequiredFields(): void
    {
        // Arrange form data to send
        $formData = [
            'old_password' => '',
            'new_password' => '',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePassword'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['old_password', 'new_password']);
    }

    public function testAuthenticatedUserFailsUpdatePasswordIfInvalidRequiredFields(): void
    {
        // Arrange form data to send
        $formData = [
            'old_password' => 'a',
            'new_password' => 'a',
            'new_password_confirmation' => 'b',
        ];

        // Act the response
        $response = $this->putJson(route('v1.users.updatePassword'), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['old_password', 'new_password']);
    }
}
