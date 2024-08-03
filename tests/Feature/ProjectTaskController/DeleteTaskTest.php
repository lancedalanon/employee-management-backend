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

    protected $project;
    protected $user;
    protected $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->withUsers(5)->create();
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
        $this->task = ProjectTask::factory()->count(5)->create(['project_id' => $this->project->project_id]);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        parent::tearDown();
    }

    public function test_delete_existing_task(): void
    {
        // Send the delete request
        $response = $this->deleteJson(route('projects.tasks.destroy', ['projectId' => $this->project->project_id, 'taskId' => $this->task->first()->project_task_id]));

        // Assert the response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        // Assert the task is soft deleted
        $this->assertSoftDeleted($this->task->first());
    }

    public function test_delete_non_existing_task(): void
    {
        // Send the delete request for a non-existing task
        $response = $this->deleteJson(route('projects.tasks.destroy', ['projectId' => $this->project->project_id, 'taskId' => 99999]));

        // Assert the response
        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }
}
