<?php

namespace Tests\Feature\ProjectController;

use App\Models\Project;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use ProjectTestingTrait, RefreshDatabase, WithFaker;

    protected $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();

        // Create a mock project for testing
        $this->project = Project::factory()->create();
    }

    protected function tearDown(): void
    {
        $this->tearDownProject();
        parent::tearDown();
    }

    public function test_can_update_project_name()
    {
        $newProjectName = $this->faker->unique()->sentence;

        $response = $this->putJson(route('admin.projects.update', ['projectId' => $this->project->project_id]), [
            'project_name' => $newProjectName,
            'project_description' => $this->project->project_description,
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
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

        $this->assertDatabaseHas('projects', [
            'project_id' => $this->project->project_id,
            'project_name' => $newProjectName,
        ]);
    }

    public function test_handles_validation_error_when_project_name_is_same()
    {
        $response = $this->putJson(route('admin.projects.update', ['projectId' => $this->project->project_id]), [
            'project_name' => $this->project->project_name,
            'project_description' => $this->project->project_description,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Project name cannot be the same as the current name.',
            ]);
    }

    public function test_returns_404_error_when_project_not_found()
    {
        $nonExistingId = 999;

        $response = $this->putJson(route('admin.projects.update', ['projectId' => $nonExistingId]), [
            'project_name' => 'Updated Project Name',
            'project_description' => 'Updated project description.',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found.',
            ]);
    }

    public function test_fails_to_update_project_name_when_exceeding_max_length()
    {
        $newProjectName = str_repeat('a', 300);

        $response = $this->putJson(route('admin.projects.update', ['projectId' => $this->project->project_id]), [
            'project_name' => $newProjectName,
            'project_description' => $this->project->project_description,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_name']);
    }

    public function test_fails_to_update_project_description_when_exceeding_max_length()
    {
        $newProjectDescription = str_repeat('a', 600);

        $response = $this->putJson(route('admin.projects.update', ['projectId' => $this->project->project_id]), [
            'project_name' => $this->project->project_name,
            'project_description' => $newProjectDescription,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_description']);
    }
}
