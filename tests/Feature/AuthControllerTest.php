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
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

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
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

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

    /**
     * Test successful logout.
     *
     * @return void
     */
    public function test_logout_success()
    {
        // Arrange
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $token = JWTAuth::fromUser($user);

        // Act
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Successfully logged out']);
    }
}
