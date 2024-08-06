<?php

namespace Tests\Feature\ProjectTaskSubtaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectTaskSubtaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetStatusesTest extends TestCase
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

    public function test_can_retrieve_subtask_statuses()
    {
        $response = $this->getJson(route('projects.tasks.subtasks.statuses.index', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'current_page',
                'data' => [
                    '*' => [
                        'project_task_subtask_status_id',
                        'project_task_subtask_id',
                        'project_task_subtask_status',
                        'project_task_subtask_status_media_file',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total'
            ]);
    }

    public function test_cannot_retrieve_subtask_statuses_if_not_authorized()
    {
        // Remove the current user from the project to simulate an unauthorized user
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->getJson(route('projects.tasks.subtasks.statuses.index', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.'
            ]);
    }
}
