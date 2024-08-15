<?php

namespace Tests\Feature\Admin\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetUsersTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

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

        // Create other users with different roles
        User::factory()->create()->assignRole($internRole);
        User::factory()->create()->assignRole($employeeRole);
        User::factory()->create()->assignRole($superRole);
        User::factory()->create()->assignRole($adminRole); // Another admin
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_it_can_retrieve_users_with_intern_and_employee_roles(): void
    {
        // Perform a GET request to the 'admin.users.index' route
        $response = $this->getJson(route('admin.users.index'));

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the returned data contains only users with 'intern' and 'employee' roles
        $response->assertJsonStructure([
            'message',
            'current_page',
            'data' => [
                '*' => [
                    'user_id',
                    'first_name',
                    'middle_name',
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
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        // Assert that no admin or super users are included in the response
        $response->assertJsonMissing(['role' => 'admin']);
        $response->assertJsonMissing(['role' => 'super']);
    }

    public function test_pagination_works_correctly(): void
    {
        // Create 15 users with 'employee' roles to test pagination (assuming default is 10 per page)
        $employeeRole = Role::where('name', 'employee')->first();
        User::factory()->count(15)->create()->each(function ($user) use ($employeeRole) {
            $user->assignRole($employeeRole);
        });

        // Perform a GET request to the 'admin.users.index' route with pagination parameters
        $response = $this->getJson(route('admin.users.index', ['page' => 2, 'per_page' => 10]));

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the second page contains the correct number of users
        $response->assertJsonFragment(['current_page' => 2]);
        $response->assertJsonFragment(['per_page' => 10]);

        // Assert that the returned data matches the expected structure
        $response->assertJsonStructure([
            'message',
            'current_page',
            'data' => [
                '*' => [
                    'user_id',
                    'first_name',
                    'middle_name',
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
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }
}
