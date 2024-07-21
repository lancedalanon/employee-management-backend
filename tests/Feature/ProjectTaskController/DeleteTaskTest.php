<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteTaskTest extends TestCase
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

    /**
     * Test deleting an existing task successfully.
     */
    public function test_delete_existing_task(): void
    {
        // Create a project and a task
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->project_id]);

        // Send the delete request
        $response = $this->deleteJson(route('projects.tasks.deleteTask', ['projectId' => $project->project_id, 'id' => $task->project_task_id]));

        // Assert the response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        // Assert the task is soft deleted
        $this->assertSoftDeleted($task);
    }

    /**
     * Test deleting a task from a non-existing project.
     */
    public function test_delete_task_from_non_existing_project(): void
    {
        // Send the delete request
        $response = $this->deleteJson(route('projects.tasks.deleteTask', ['projectId' => 999, 'id' => 1]));

        // Assert the response
        $response->assertStatus(404)
            ->assertJson(['message' => 'Project not found.']);
    }

    /**
     * Test deleting a non-existing task.
     */
    public function test_delete_non_existing_task(): void
    {
        // Create a project
        $project = Project::factory()->create();

        // Send the delete request for a non-existing task
        $response = $this->deleteJson(route('projects.tasks.deleteTask', ['projectId' => $project->project_id, 'id' => 999]));

        // Assert the response
        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }
}
