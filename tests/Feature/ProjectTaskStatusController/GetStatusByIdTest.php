<?php

namespace Tests\Feature\ProjectTaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetStatusByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $task;
    protected $status;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with known credentials and authenticate
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create a project and task
        $this->project = Project::factory()->create();
        $this->task = ProjectTask::factory()->create([
            'project_id' => $this->project->project_id,
        ]);

        // Create ProjectTaskStatus with the ID of the created ProjectTask
        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->project_task_id,
        ]);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->status = null;
        parent::tearDown();
    }

    public function test_should_retrieve_status_by_id()
    {
        $response = $this->getJson(route('projects.tasks.statuses.getStatusById', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'id' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_status_id',
                    'project_task_status',
                    'project_task_id',
                    'project_task_status_media_file',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ]);
    }

    public function test_should_return_404_when_task_not_found()
    {
        $response = $this->getJson(route('projects.tasks.statuses.getStatusById', [
            'projectId' => $this->project->project_id,
            'taskId' => 9999, // Non-existent task ID
            'id' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Task not found.',
            ]);
    }
}
