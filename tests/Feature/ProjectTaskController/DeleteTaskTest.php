<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
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
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        parent::tearDown();
    }

    public function test_delete_existing_task(): void
    {
        // Create a project and a task
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->project_id]);

        // Send the delete request
        $response = $this->deleteJson(route('projects.tasks.destroy', ['projectId' => $project->project_id, 'taskId' => $task->project_task_id]));

        // Assert the response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        // Assert the task is soft deleted
        $this->assertSoftDeleted($task);
    }

    public function test_delete_non_existing_task(): void
    {
        // Create a project
        $project = Project::factory()->create();

        // Send the delete request for a non-existing task
        $response = $this->deleteJson(route('projects.tasks.destroy', ['projectId' => $project->project_id, 'taskId' => 99999]));

        // Assert the response
        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }
}
