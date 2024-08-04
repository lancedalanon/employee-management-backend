<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteSubtaskTest extends TestCase
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
        $this->subtask = ProjectTaskSubtask::factory()->create(['project_task_id' => $this->task->project_task_id]);
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->subtask = null;
        parent::tearDown();
    }

    public function test_can_delete_a_subtask()
    {
        $response = $this->deleteJson(route('projects.tasks.subtasks.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtask deleted successfully.',
            ]);

        $this->assertSoftDeleted('project_task_subtasks', [
            'project_task_subtask_id' => $this->subtask->project_task_subtask_id,
        ]);
    }

    public function test_returns_forbidden_when_user_is_unauthorized()
    {
        $anotherUser = User::factory()->create();
        $this->actingAs($anotherUser);

        $response = $this->deleteJson(route('projects.tasks.subtasks.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_returns_not_found_when_subtask_does_not_exist()
    {
        $response = $this->deleteJson(route('projects.tasks.subtasks.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => 99999,
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Subtask not found.',
            ]);
    }
}
