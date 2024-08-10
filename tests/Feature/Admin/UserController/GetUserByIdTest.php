<?php

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetUserByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $internUser;
    protected $employeeUser;
    protected $superUser;
    protected $adminUser2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $internRole = Role::create(['name' => 'intern']);
        $employeeRole = Role::create(['name' => 'employee']);
        $superRole = Role::create(['name' => 'super']);

        // Create an admin user and act as that user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);
        Sanctum::actingAs($this->adminUser);

        // Create users with different roles
        $this->internUser = User::factory()->create();
        $this->internUser->assignRole($internRole);

        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole($employeeRole);

        $this->superUser = User::factory()->create();
        $this->superUser->assignRole($superRole);

        $this->adminUser2 = User::factory()->create();
        $this->adminUser2->assignRole($adminRole);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_can_retrieve_user_with_intern_role(): void
    {
        // Perform a GET request to retrieve the intern user
        $response = $this->getJson(route('admin.users.show', ['userId' => $this->internUser->user_id]));

        // Assert the response status is 200 OK
        $response->assertStatus(200);

        // Assert the user data contains the correct role
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user_id',
                'first_name',
                'middle_name' ,
                'last_name',
                'place_of_birth',
                'date_of_birth',
                'gender',
                'email',
                'username',
                'recovery_email',
                'phone_number',
                'emergency_contact_name',
                'emergency_contact_number',
                'role',
            ],
        ]);
    }

    public function test_can_retrieve_user_with_employee_role(): void
    {
        // Perform a GET request to retrieve the employee user
        $response = $this->getJson(route('admin.users.show', ['userId' => $this->employeeUser->user_id]));

        // Assert the response status is 200 OK
        $response->assertStatus(200);

        // Assert the user data contains the correct role
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user_id',
                'first_name',
                'middle_name' ,
                'last_name',
                'place_of_birth',
                'date_of_birth',
                'gender',
                'email',
                'username',
                'recovery_email',
                'phone_number',
                'emergency_contact_name',
                'emergency_contact_number',
                'role',
            ],
        ]);
    }

    public function test_cannot_retrieve_user_with_admin_role(): void
    {
        // Perform a GET request to retrieve an admin user (should fail)
        $response = $this->getJson(route('admin.users.show', ['userId' => $this->adminUser2->user_id]));

        // Assert the response status is 404 Not Found
        $response->assertStatus(404);

        // Assert the error message
        $response->assertJsonFragment([
            'message' => 'User not found.',
        ]);
    }

    public function test_cannot_retrieve_user_with_super_role(): void
    {
        // Perform a GET request to retrieve the super user (should fail)
        $response = $this->getJson(route('admin.users.show', ['userId' => $this->superUser->user_id]));

        // Assert the response status is 404 Not Found
        $response->assertStatus(404);

        // Assert the error message
        $response->assertJsonFragment([
            'message' => 'User not found.',
        ]);
    }

    public function test_cannot_retrieve_non_existent_user(): void
    {
        // Perform a GET request to a non-existent user ID
        $response = $this->getJson(route('admin.users.show', ['userId' => 99999]));

        // Assert the response status is 404 Not Found
        $response->assertStatus(404);

        // Assert the error message
        $response->assertJsonFragment([
            'message' => 'User not found.',
        ]);
    }
}
