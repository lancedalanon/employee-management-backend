<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Testing\ProjectTestingTrait;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetProjectsTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        $this->tearDownProject();
        parent::tearDown();
    }

    /**
     * Test the getProjects endpoint with pagination.
     *
     * @return void
     */
    public function test_get_projects()
    {
        // Send a GET request to the projects endpoint
        $response = $this->getJson(route('projects.getProjects'));

        // Assert that the response is successful
        $response->assertStatus(200);

        // Assert that the JSON response has the correct structure
        $response->assertJsonStructure([
            'current_page',
            'data' => [
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
                            'full_name',
                            'username',
                        ]
                    ]
                ]
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links' => [
                '*' => [
                    'url',
                    'label',
                    'active'
                ]
            ],
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total'
        ]);
    }
}
