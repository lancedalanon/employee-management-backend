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

        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);

        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->first()->project_task_id,
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
        $response = $this->getJson(route('projects.tasks.statuses.show', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
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

    public function test_should_return_403_if_unauthorized_to_get_status_by_id()
    {
        $response = $this->getJson(route('projects.tasks.statuses.show', [
            'projectId' => $this->project->project_id,
            'taskId' => 99999,
            'statusId' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }
}
