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

class DeleteStatusTest extends TestCase
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

    public function test_can_delete_subtask_status_successfully()
    {
        $response = $this->deleteJson(route('projects.tasks.subtasks.statuses.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id,
        ]));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Subtask status deleted successfully.']);
        $this->assertSoftDeleted('project_task_subtask_statuses', [
            'project_task_subtask_status_id' => $this->subtaskStatus->project_task_subtask_status_id
        ]);
    }

    public function test_unauthorized_deletion()
    {
        // Change user to one without authorization
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson(route('projects.tasks.subtasks.statuses.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id,
        ]));

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Forbidden.']);
    }

    public function test_deletion_of_non_existent_status()
    {
        $response = $this->deleteJson(route('projects.tasks.subtasks.statuses.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => 99999,
        ]));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Subtask status not found.']);
    }
}
