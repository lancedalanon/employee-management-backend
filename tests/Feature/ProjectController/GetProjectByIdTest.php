<?php

namespace Tests\Feature\ProjectController;

use App\Models\Project;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetProjectByIdTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    protected $project;
    protected $user;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->withUsers(5)->create();
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
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
        $response = $this->getJson(route('projects.show', ['projectId' => $this->project->project_id]));

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
        $response = $this->getJson(route('projects.show', ['projectId' => 99999]));

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert that the JSON response contains the error message
        $response->assertJson([
            'message' => 'Project not found.'
        ]);
    }
}
