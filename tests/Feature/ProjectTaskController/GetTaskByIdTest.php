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

class GetTaskByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $project;
    protected $user;
    protected $task;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_returns_specific_task()
    {
        // Make a GET request to fetch the task
        $response = $this->getJson(route('projects.tasks.show', ['projectId' => $this->project->project_id, 'taskId' => $this->task->first()->project_task_id]));

        // Assert response status is 200 and structure of returned JSON
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_id',
                    'project_id',
                    'project_task_name',
                    'project_task_description',
                    'project_task_progress',
                    'project_task_priority_level',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ]);
    }

    public function test_returns_not_found_if_task_does_not_exist()
    {
        // Make a GET request with existing project ID but non-existent task ID
        $response = $this->getJson(route('projects.tasks.show', ['projectId' => $this->project->project_id, 'taskId' => 99999]));

        // Assert response status is 404
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Task not found.',
            ]);
    }
}
