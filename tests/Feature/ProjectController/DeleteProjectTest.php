<?php

namespace Tests\Feature\ProjectController;

use App\Models\Project;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeleteProjectTest extends TestCase
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

        // Create a sample project
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
     * Test deleting a project by ID.
     *
     * @return void
     */
    public function test_delete_project_by_id()
    {
        // Send a DELETE request to delete the project
        $response = $this->deleteJson(route('admin.projects.deleteProject', ['projectId' => $this->project->project_id]));

        // Assert that the response is successful (200 OK)
        $response->assertStatus(200);

        // Assert that the JSON response contains the success message
        $response->assertJson([
            'message' => 'Project deleted successfully.',
        ]);

        // Assert that the project has been soft deleted in the database
        $this->assertSoftDeleted('projects', ['project_id' => $this->project->project_id]);
    }

    /**
     * Test deleting a non-existent project.
     *
     * @return void
     */
    public function test_delete_non_existent_project()
    {
        // Non-existent project ID
        $nonExistentId = 9999;

        // Send a DELETE request to delete the project
        $response = $this->deleteJson(route('admin.projects.deleteProject', ['projectId' => $nonExistentId]));

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert that the JSON response contains the error message
        $response->assertJson([
            'message' => 'Project not found.',
        ]);
    }
}
