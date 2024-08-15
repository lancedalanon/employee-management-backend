<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetSubtaskByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $project;

    protected $task;

    protected $subtask;

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
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->subtask = null;
        parent::tearDown();
    }

    public function test_returns_subtask_for_existing_project(): void
    {
        $response = $this->getJson(route(
            'projects.tasks.subtasks.show',
            [
                'projectId' => $this->project->project_id,
                'taskId' => $this->task->first()->project_task_id,
                'subtaskId' => $this->subtask->first()->project_task_subtask_id,
            ]
        ));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_subtask_id',
                    'project_task_id',
                    'project_task_subtask_name',
                    'project_task_subtask_description',
                    'project_task_subtask_progress',
                    'project_task_subtask_priority_level',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ]);
    }
}
