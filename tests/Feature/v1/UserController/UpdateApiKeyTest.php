<?php

namespace Tests\Feature\v1\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateApiKeyTest extends TestCase
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

    public function testAuthenticatedUserCanUpdateApiKey(): void
    {
        // Arrange a string with 32 characters
        $apiKey = str_repeat('a', 32);
        
        // Arrange the header data
        $headers = [
            'X-API-Key' => $apiKey,
        ];
    
        // Act to send the request with the custom header
        $response = $this->putJson(route('v1.users.updateApiKey'), [], $headers);
    
        // Assert the response status is 200 OK
        $response->assertStatus(200);
    
        // Fetch the user from the database
        $user = User::find($this->user->user_id);
    
        // Handle case where user is not found
        if (!$user) {
            $this->fail('User not found in the database.');
        }
    
        // Decrypt the API key from the database to compare with the original
        $decryptedApiKey = Crypt::decryptString($user->api_key);
    
        // Assert that the decrypted API key matches the original API key
        $this->assertEquals($apiKey, $decryptedApiKey);
    }

    public function testAuthenticatedUserFailsToUpdateApiKeyWithMissingField(): void
    {
        // Arrange the header data
        $headers = [
            'X-API-Key' => '',
        ];
    
        // Act to send the request with the custom header
        $response = $this->putJson(route('v1.users.updateApiKey'), [], $headers);
    
        // Assert the response status is 400
        $response->assertStatus(400)
                ->assertJson(['message' => 'API key is required.']);
    }

    public function testAuthenticatedUserFailsToUpdateApiKeyWithInvalidField(): void
    {
        // Arrange a string with 501 characters
        $apiKey = str_repeat('a', 501);

        // Arrange the header data
        $headers = [
            'X-API-Key' => $apiKey,
        ];
    
        // Act to send the request with the custom header
        $response = $this->putJson(route('v1.users.updateApiKey'), [], $headers);
    
        // Assert the response status is 422
        $response->assertStatus(422)
                ->assertJson(['message' => 'Invalid API key format.']);
    }
}
