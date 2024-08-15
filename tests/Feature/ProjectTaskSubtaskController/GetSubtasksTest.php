<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetSubtasksTest extends TestCase
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

    public function test_returns_subtasks_for_existing_project()
    {
        $response = $this->getJson(route(
            'projects.tasks.subtasks.index',
            [
                'projectId' => $this->project->project_id,
                'taskId' => $this->task->first()->project_task_id,
            ]
        ));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'current_page',
                'data' => [
                    '*' => [
                        'project_task_subtask_id',
                        'project_task_id',
                        'project_task_subtask_name',
                        'project_task_subtask_description',
                        'project_task_subtask_progress',
                        'project_task_subtask_priority_level',
                    ],
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
                'total',
            ]);
    }
}
