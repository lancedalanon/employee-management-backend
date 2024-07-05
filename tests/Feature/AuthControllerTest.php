<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user login with valid credentials.
     *
     * @return void
     */
    public function test_login_with_valid_credentials()
    {
        // Create a user with known credentials
        $password = 'password123';
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt($password),
        ]);

        // Make a POST request to the login endpoint with valid credentials
        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => $password,
        ]);

        // Assert that the response is successful and contains the expected data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'username',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'username' => 'testuser',
                ],
                'message' => 'User logged in successfully.',
            ]);
    }

    /**
     * Test user login with invalid credentials.
     *
     * @return void
     */
    public function test_login_with_invalid_credentials()
    {
        // Create a user with known credentials
        $password = 'password123';
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt($password),
        ]);

        // Make a POST request to the login endpoint with invalid credentials
        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        // Assert that the response is unauthorized
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors' => ['error' => 'Invalid credentials']
            ]);
    }

    /**
     * Test user logout.
     *
     * @return void
     */
    public function test_logout()
    {
        // Create a user and generate a token for the user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Make a POST request to the logout endpoint with the token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        // Assert that the response is successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User logged out successfully.',
            ]);
    }
}
