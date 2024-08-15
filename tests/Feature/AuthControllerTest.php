<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $password;

    /**
     * Set up the test environment.
     *
     * This method is called before each test method runs.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define a common password
        $this->password = 'password123';

        // Create a user with known credentials
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt($this->password),
        ]);
    }

    /**
     * Tear down the test environment.
     *
     * This method is called after each test method runs.
     */
    protected function tearDown(): void
    {
        // Clear user data if needed (database is refreshed by RefreshDatabase)
        $this->user = null;

        parent::tearDown();
    }

    /**
     * Test user login with valid credentials.
     *
     * @return void
     */
    public function test_login_with_valid_credentials()
    {
        // Make a POST request to the login endpoint with valid credentials
        $response = $this->postJson('/api/login', [
            'username' => $this->user->username,
            'password' => $this->password,
        ]);

        // Assert that the response is successful and contains the expected data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'username',
                ],
                'message',
            ])
            ->assertJson([
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
        // Make a POST request to the login endpoint with invalid credentials
        $response = $this->postJson('/api/login', [
            'username' => $this->user->username,
            'password' => 'wrongpassword',
        ]);

        // Assert that the response is unauthorized
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test user logout.
     *
     * @return void
     */
    public function test_logout()
    {
        // Generate a token for the user
        $token = $this->user->createToken('auth_token')->plainTextToken;

        // Make a POST request to the logout endpoint with the token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        // Assert that the response is successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User logged out successfully.',
            ]);
    }

    /**
     * Test sending password reset link.
     *
     * @return void
     */
    public function test_send_reset_link_email()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/password/email', [
            'email' => $user->email,
        ]);

        $response->assertJson(['message' => 'Password reset link sent successfully.']);
        $response->assertStatus(200);
    }

    /**
     * Test resetting user's password.
     *
     * @return void
     */
    public function test_reset_password()
    {
        // Create a password reset token
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $newPassword = $this->faker->password(8);

        $response = $this->postJson('/api/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertJson(['message' => 'Your password has been reset.']);
        $response->assertStatus(200);

        // Assert that the user's password was actually reset in the database
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }
}
