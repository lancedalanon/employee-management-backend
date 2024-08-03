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

class UpdateTaskTest extends TestCase
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

    public function test_updates_a_task_successfully()
    {
        $response = $this->putJson(route('projects.tasks.update', ['projectId' => $this->project->project_id, 'taskId' => $this->task->first()->project_task_id]), [
            'project_task_name' => 'Updated Task Name',
            'project_task_description' => 'Updated Task Description',
            'project_task_progress' => 'In progress',
            'project_task_priority_level' => 'High',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'project_task_name',
                'project_task_description',
                'project_task_progress',
                'project_task_priority_level',
                'updated_at',
                'created_at',
                'project_id',
                'project_task_id',
            ],
        ]);
    }

    public function test_returns_validation_error_when_required_fields_are_missing()
    {
        $response = $this->putJson(route('projects.tasks.update', ['projectId' => $this->project->project_id, 'taskId' => $this->task->first()->project_task_id]), []);

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
        $response = $this->putJson(route('projects.tasks.update', ['projectId' => $this->project->project_id, 'taskId' => $this->task->first()->project_task_id]), [
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

    public function test_returns_not_found_when_task_does_not_exist()
    {
        $response = $this->putJson(route('projects.tasks.update', ['projectId' => $this->project->project_id, 'taskId' => 999999]), [
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
