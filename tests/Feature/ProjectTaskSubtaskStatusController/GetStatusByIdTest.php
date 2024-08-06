<?php

namespace Tests\Feature\ProjectTaskSubtaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectTaskSubtaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetStatusByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $task;
    protected $subtask;
    protected $subtaskStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);

        $this->subtask = ProjectTaskSubtask::factory()->create([
            'project_task_id' => $this->task->first()->project_task_id,
        ]);

        $this->subtaskStatus = ProjectTaskSubtaskStatus::factory()->create([
            'project_task_subtask_id' => $this->subtask->first()->project_task_subtask_id
        ]);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->subtask = null;
        $this->subtaskStatus = null;
        parent::tearDown();
    }

    public function test_can_retrieve_subtask_status_by_id()
    {
        $response = $this->getJson(route('projects.tasks.subtasks.statuses.show', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->first()->project_task_subtask_status_id
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_subtask_status_id',
                    'project_task_subtask_id',
                    'project_task_subtask_status',
                    'project_task_subtask_status_media_file',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
            ]);
    }

    public function test_cannot_retrieve_nonexistent_subtask_status()
    {
        $response = $this->getJson(route('projects.tasks.subtasks.statuses.show', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id,
            'subtaskStatusId' => 99999
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Subtask status not found.'
            ]);
    }

    public function test_cannot_retrieve_subtask_status_if_not_authorized()
    {
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->getJson(route('projects.tasks.subtasks.statuses.show', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.'
            ]);
    }
}
