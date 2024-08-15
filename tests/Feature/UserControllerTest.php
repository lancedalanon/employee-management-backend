<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;

    /**
     * Set up the test environment.
     *
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user
        $this->user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);
        Sanctum::actingAs($this->user);
    }

    /**
     * Tear down the test environment.
     *
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        // Clean up any resources allocated during the test
        $this->user = null;

        parent::tearDown();
    }

    /**
     * Test retrieving the authenticated user's information.
     *
     * @return void
     */
    public function test_show_authenticated_user()
    {
        // Send a GET request to the authenticated user endpoint
        $response = $this->getJson(route('users.show'));

        // Assert the response status
        $response->assertStatus(200);

        // Assert the response contains the expected fields
        $response->assertJsonFragment([
            'first_name' => $this->user->first_name,
            'middle_name' => $this->user->middle_name,
            'last_name' => $this->user->last_name,
            'place_of_birth' => $this->user->place_of_birth,
            'date_of_birth' => $this->user->date_of_birth,
            'gender' => $this->user->gender,
            'email' => $this->user->email,
            'username' => $this->user->username,
            'recovery_email' => $this->user->recovery_email,
            'phone_number' => $this->user->phone_number,
            'emergency_contact_name' => $this->user->emergency_contact_name,
            'emergency_contact_number' => $this->user->emergency_contact_number,
            'user_id' => $this->user->user_id,
        ]);
    }

    /**
     * Test updating the authenticated user's personal information.
     *
     * @return void
     */
    public function test_update_personal_information()
    {
        // Prepare update data
        $updateData = [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'place_of_birth' => $this->faker->city,
            'date_of_birth' => $this->faker->date,
            'gender' => 'Male',
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->userName,
            'recovery_email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->regexify('09[0-9]{2}-[0-9]{3}-[0-9]{4}'),
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_number' => $this->faker->regexify('09[0-9]{2}-[0-9]{3}-[0-9]{4}'),
        ];

        // Send a PUT request to the update personal information endpoint
        $response = $this->putJson(route('users.updatePersonalInformation'), $updateData);

        // Assert the response status and structure
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Personal information updated successfully',
                'data' => array_merge($updateData, ['user_id' => $this->user->user_id]),
            ]);

        // Assert the database has been updated
        $this->assertDatabaseHas('users', array_merge(['user_id' => $this->user->user_id], $updateData));
    }

    /**
     * Test successfully changing the password.
     *
     * @return void
     */
    public function test_change_password()
    {
        // Act as the created user
        $response = $this->putJson(route('users.updatePassword'), [
            'old_password' => 'oldpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Password changed successfully.',
        ]);

        // Assert the password was changed in the database
        $this->assertTrue(Hash::check('newpassword', $this->user->fresh()->password));
    }

    /**
     * Test changing the password with an incorrect old password.
     *
     * @return void
     */
    public function test_change_password_with_incorrect_old_password()
    {
        // Act as the created user
        $response = $this->putJson(route('users.updatePassword'), [
            'old_password' => 'wrongpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        // Assert the response has validation errors
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The old password does not match our records.',
        ]);
    }
}
