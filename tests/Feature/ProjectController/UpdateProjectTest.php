<?php

namespace Tests\Feature\ProjectController;

use App\Models\Project;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    protected $project;
    protected $data;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();

        // Create a mock project
        $this->project = Project::factory()->create();

        $this->data = [
            'project_name' => 'Updated Project Name',
            'project_description' => 'Updated project description.',
        ];
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
     * Test updating a project with valid data.
     *
     * @return void
     */
    public function test_update_project_with_valid_data()
    {
        // Send a PUT request to update the project
        $response = $this->putJson(route('admin.projects.updateProject', ['id' => $this->project->project_id]), $this->data);

        // Assert that the response is successful (200 OK)
        $response->assertStatus(200);

        // Assert that the updated project data matches the request data
        $this->assertDatabaseHas('projects', [
            'project_id' => $this->project->project_id,
            'project_name' => 'Updated Project Name',
            'project_description' => 'Updated project description.',
        ]);
    }

    /**
     * Test updating a project with invalid data (validation error).
     *
     * @return void
     */
    public function test_update_project_with_invalid_data()
    {
        // Invalid project data (missing project_name)
        $invalidData = [
            'project_description' => 'Updated project description.',
        ];

        // Send a PUT request to update the project
        $response = $this->putJson(route('admin.projects.updateProject', ['id' => $this->project->project_id]), $invalidData);

        // Assert that the response status is 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the JSON response contains the validation error message
        $response->assertJsonValidationErrors('project_name');
    }

    /**
     * Test updating a non-existent project.
     *
     * @return void
     */
    public function test_update_non_existent_project()
    {
        // Non-existent project ID
        $nonExistentId = 9999;

        // Send a PUT request to update the project
        $response = $this->putJson(route('admin.projects.updateProject', ['id' => $nonExistentId]), $this->data);

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert that the JSON response contains the error message
        $response->assertJson([
            'message' => 'Project not found.',
        ]);
    }
}
