<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserControllerTest extends TestCase
{
    use RefreshDatabase; // Refresh the database before each test

    /**
     * Test updating user's personal information.
     *
     * @return void
     */
    public function testUpdatePersonalInformation()
    {
        // Arrange
        // Create a test user
        $user = User::factory()->create();

        // Generate a JWT token for the test user
        $token = JWTAuth::fromUser($user);

        // Define the request payload
        $payload = [
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ];

        // Act
        // Make a PATCH request to update the user's personal information
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token, // Set the JWT token in the headers
        ])->put('/api/personal-information', $payload);

        // Assert
        // Assert that the response is successful
        $response->assertStatus(200);

        // Assert that the user's information is updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ]);
    }
}
