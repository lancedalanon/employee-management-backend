<?php

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'intern']);
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full-time']);
        Role::create(['name' => 'part-time']);
        Role::create(['name' => 'day-shift']);
        Role::create(['name' => 'afternoon-shift']);
        Role::create(['name' => 'evening-shift']);
        Role::create(['name' => 'early-shift']);
        Role::create(['name' => 'late-shift']);

        // Create an admin user and act as that user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Sanctum::actingAs($this->adminUser);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_can_create_user_with_valid_data(): void
    {
        $userData = [
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'suffix' => 'Jr.',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'username' => 'johnsmith',
            'email' => 'john.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->postJson(route('admin.users.store'), $userData);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'User created successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'johnsmith',
            'email' => 'john.smith@example.com',
        ]);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $userData = [
            // 'first_name' => 'John', // Required field omitted
            'last_name' => 'Smith',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'username' => 'johnsmith',
            'email' => 'john.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->postJson(route('admin.users.store'), $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name']);
    }

    public function test_validation_fails_for_invalid_field_values(): void
    {
        $userData = [
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'suffix' => 'Jr.',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Unknown', // Invalid gender value
            'username' => 'johnsmith',
            'email' => 'not-an-email', // Invalid email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->postJson(route('admin.users.store'), $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender', 'email']);
    }

    public function test_validation_fails_for_duplicate_username_or_email(): void
    {
        // Create a user with a specific username and email
        User::factory()->create([
            'username' => 'johnsmith',
            'email' => 'john.smith@example.com',
        ]);

        $userData = [
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'suffix' => 'Jr.',
            'place_of_birth' => 'New York',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'username' => 'johnsmith', // Duplicate username
            'email' => 'john.smith@example.com', // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->postJson(route('admin.users.store'), $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'email']);
    }
}
