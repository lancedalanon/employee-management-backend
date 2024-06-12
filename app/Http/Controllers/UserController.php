<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test successful update of personal information.
     *
     * @return void
     */
    public function testUpdatePersonalInformationSuccess()
    {
        // Arrange
        $user = User::factory()->create();
        $jwtCookie = cookie('jwt_user_id', $user->id, 60);

        $requestData = [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'place_of_birth' => $this->faker->city,
            'date_of_birth' => $this->faker->date,
            'gender' => 'male',
        ];

        // Act
        $response = $this->withCookie('jwt_user_id', $jwtCookie)->putJson('/api/user/update', $requestData);

        // Assert
        $response->assertStatus(200)
            ->assertJson(['message' => 'Personal information updated successfully']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => $requestData['first_name'],
            'middle_name' => $requestData['middle_name'],
            'last_name' => $requestData['last_name'],
            'place_of_birth' => $requestData['place_of_birth'],
            'date_of_birth' => $requestData['date_of_birth'],
            'gender' => $requestData['gender'],
        ]);
    }

    /**
     * Test validation error response when updating personal information.
     *
     * @return void
     */
    public function testUpdatePersonalInformationValidationError()
    {
        // Arrange
        $user = User::factory()->create();
        $jwtCookie = cookie('jwt_user_id', $user->id, 60);

        $requestData = [
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'place_of_birth' => '',
            'date_of_birth' => '',
            'gender' => '',
        ];

        // Act
        $response = $this->withCookie('jwt_user_id', $jwtCookie)->putJson('/api/user/update', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['first_name', 'last_name', 'place_of_birth', 'date_of_birth', 'gender']]);
    }

    /**
     * Test error response when an exception occurs.
     *
     * @return void
     */
    public function testUpdatePersonalInformationException()
    {
        // Arrange
        $this->mock(User::class, function ($mock) {
            $mock->shouldReceive('findOrFail')->andThrow(new \Exception('Test exception'));
        });

        $user = User::factory()->create();
        $jwtCookie = cookie('jwt_user_id', $user->id, 60);

        $requestData = [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'place_of_birth' => $this->faker->city,
            'date_of_birth' => $this->faker->date,
            'gender' => 'male',
        ];

        // Act
        $response = $this->withCookie('jwt_user_id', $jwtCookie)->putJson('/api/user/update', $requestData);

        // Assert
        $response->assertStatus(500)
            ->assertJson(['message' => 'Failed to update personal information', 'error' => 'Test exception']);
    }
}
