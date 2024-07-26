<?php

namespace Tests\Feature\ProjectController;

use App\Models\Project;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetProjectByIdTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    protected $project;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();

        // Create a mock project
        $this->project = Project::factory()->create();
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
     * Test retrieving an existing project by ID.
     *
     * @return void
     */
    public function test_get_existing_project_by_id()
    {
        // Send a GET request to the endpoint with the project ID
        $response = $this->getJson(route('projects.getProjectsById', ['projectId' => $this->project->project_id]));

        // Assert that the response is successful (200 OK)
        $response->assertStatus(200);

        // Assert that the JSON response matches the expected structure
        $response->assertJsonStructure([
            'message',
            'data' => [
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
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test retrieving a non-existing project by ID.
     *
     * @return void
     */
    public function test_get_non_existing_project_by_id()
    {
        // Send a GET request to the endpoint with a non-existent project ID
        $response = $this->getJson(route('projects.getProjectsById', ['projectId' => 9999]));

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert that the JSON response contains the error message
        $response->assertJson([
            'message' => 'Project not found.'
        ]);
    }
}
