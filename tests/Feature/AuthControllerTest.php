<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login.
     *
     * @return void
     */
    public function test_login_success()
    {
        // Arrange
        // Create a test user
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        // Define login data
        $loginData = [
            'username' => 'testuser',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/login', $loginData);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    /**
     * Test login failure due to invalid credentials.
     *
     * @return void
     */
    public function test_login_failure_invalid_credentials()
    {
        // Arrange
        // Create a test user
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        // Define login data with wrong password
        $loginData = [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ];

        // Act
        $response = $this->postJson('/api/login', $loginData);

        // Assert
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid credentials']);
    }
}
