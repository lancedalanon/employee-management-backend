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
        // Arrange the form data
        $formData = [
            'api_key' => 'INSERT_ANY_API_KEY_EXAMPLE_HERE_THAT_IS_32_CHARACTERS_OR_MORE',
        ];
    
        // Act to send the request with the custom form
        $response = $this->putJson(route('v1.users.updateApiKey'), $formData);

        dd($response);

        // Assert the response status is 200
        $response->assertStatus(200)
                ->assertJson(['message' => 'API key updated successfully.']);
    }

    public function testAuthenticatedUserFailsToUpdateApiKeyWithMissingField(): void
    {
        // Arrange the form data
        $formData = [
            'api_key' => '',
        ];
    
        // Act to send the request with the custom form
        $response = $this->putJson(route('v1.users.updateApiKey'), $formData);
    
        // Assert the response status is 422
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['api_key']);
    }

    public function testAuthenticatedUserFailsToUpdateApiKeyWithInvalidField(): void
    {
        // Arrange a string with 501 characters
        $apiKey = str_repeat('a', 501);

        // Arrange the form data
        $formData = [
            'api_key' => $apiKey,
        ];

        // Act to send the request with the custom form
        $response = $this->putJson(route('v1.users.updateApiKey'), $formData);
    
        // Assert the response status is 422
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['api_key']);
    }
}
