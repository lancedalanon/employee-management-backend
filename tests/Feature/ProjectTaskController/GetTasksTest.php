<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetTasksTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with known credentials and authenticate
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        parent::tearDown();
    }

    public function test_returns_tasks_for_existing_project()
    {
        // Create a project
        $project = Project::factory()->create();

        // Create some tasks for the project
        ProjectTask::factory()->count(5)->create(['project_id' => $project->project_id]);

        // Hit the endpoint to get tasks for the created project
        $response = $this->getJson(route('projects.tasks.getTasks', ['projectId' => $project->project_id]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'project_task_id',
                        'project_id',
                        'project_task_name',
                        'project_task_description',
                        'project_task_progress',
                        'project_task_priority_level',
                    ]
                ]
            ]);
    }

    public function test_returns_404_if_project_not_found()
    {
        $nonExistingProjectId = 999; // Assuming this ID does not exist in the database

        // Hit the endpoint with a non-existing project ID
        $response = $this->getJson(route('projects.tasks.getTasks', ['projectId' => $nonExistingProjectId]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found.',
            ]);
    }

    public function test_returns_404_if_no_tasks_found_for_project()
    {
        // Create a project without any tasks
        $project = Project::factory()->create();

        // Hit the endpoint to get tasks for the created project (which has no tasks)
        $response = $this->getJson(route('projects.tasks.getTasks', ['projectId' => $project->project_id]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Task not found.',
            ]);
    }
}
