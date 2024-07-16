<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        $adminRole = Role::create(['name' => 'admin']);

        // Create a dummy admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        // Authenticate the admin user
        Sanctum::actingAs($this->admin);

        // Create projects with users
        Project::factory()->count(3)->withUsers(5)->create();
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test the getProjects endpoint.
     *
     * @return void
     */
    public function test_get_projects()
    {
        // Send a GET request to the projects endpoint
        $response = $this->getJson(route('admin.projects.getProjects'));

        // Assert that the response is successful
        $response->assertStatus(200);

        // Assert that the JSON response has the correct structure
        $response->assertJsonStructure([
            '*' => [
                'project_id',
                'project_name',
                'project_description',
                'created_at',
                'updated_at',
                'deleted_at',
                'users' => [
                    '*' => [
                        'user_id',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'place_of_birth',
                        'date_of_birth',
                        'gender',
                        'username',
                        'email',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]
        ]);
    }
}
