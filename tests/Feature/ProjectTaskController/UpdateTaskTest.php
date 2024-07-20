<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
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

    public function test_updates_a_task_successfully()
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->project_id]);

        $response = $this->putJson(route('projects.tasks.updateTask', ['projectId' => $project->project_id, 'id' => $task->project_task_id]), [
            'project_task_name' => 'Updated Task Name',
            'project_task_description' => 'Updated Task Description',
            'project_task_progress' => 'In progress',
            'project_task_priority_level' => 'High',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Task updated successfully.',
            'data' => [
                'project_task_name' => 'Updated Task Name',
                'project_task_description' => 'Updated Task Description',
                'project_task_progress' => 'In progress',
                'project_task_priority_level' => 'High',
            ],
        ]);
    }

    public function test_returns_validation_error_when_required_fields_are_missing()
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->project_id]);

        $response = $this->putJson(route('projects.tasks.updateTask', ['projectId' => $project->project_id, 'id' => $task->project_task_id]), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_task_name',
            'project_task_description',
            'project_task_progress',
            'project_task_priority_level',
        ]);
    }

    public function test_returns_validation_error_when_fields_are_invalid()
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->project_id]);

        $response = $this->putJson(route('projects.tasks.updateTask', ['projectId' => $project->project_id, 'id' => $task->project_task_id]), [
            'project_task_name' => '',
            'project_task_description' => '',
            'project_task_progress' => '',
            'project_task_priority_level' => 'Invalid Priority',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_task_name',
            'project_task_description',
            'project_task_progress',
            'project_task_priority_level',
        ]);
    }

    public function test_returns_not_found_when_project_does_not_exist()
    {
        $response = $this->putJson(route('projects.tasks.updateTask', ['projectId' => 999, 'id' => 1]), [
            'project_task_name' => 'Updated Task Name',
            'project_task_description' => 'Updated Task Description',
            'project_task_progress' => 'In progress',
            'project_task_priority_level' => 'High',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Project not found.',
        ]);
    }

    public function test_returns_not_found_when_task_does_not_exist()
    {
        $project = Project::factory()->create();

        $response = $this->putJson(route('projects.tasks.updateTask', ['projectId' => $project->project_id, 'id' => 999]), [
            'project_task_name' => 'Updated Task Name',
            'project_task_description' => 'Updated Task Description',
            'project_task_progress' => 'In progress',
            'project_task_priority_level' => 'High',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Task not found.',
        ]);
    }
}
