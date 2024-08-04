<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateSubtaskTest extends TestCase
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

    public function test_update_subtask()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]), [
            'project_task_subtask_name' => 'Updated Subtask',
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 'In progress',
            'project_task_subtask_priority_level' => 'Medium',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtask updated successfully.',
                'data' => [
                    'project_task_subtask_name' => 'Updated Subtask',
                    'project_task_subtask_description' => 'Updated description',
                    'project_task_subtask_progress' => 'In progress',
                    'project_task_subtask_priority_level' => 'Medium',
                ],
            ]);
    }

    public function test_update_subtask_unauthorized()
    {
        $unauthorizedUser = Project::factory()->withUsers(1)->create()->users()->first();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]), [
            'project_task_subtask_name' => 'Updated Subtask',
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 'In progress',
            'project_task_subtask_priority_level' => 'Medium',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_update_subtask_not_found()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => 999999,
        ]), [
            'project_task_subtask_name' => 'Updated Subtask',
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 'In progress',
            'project_task_subtask_priority_level' => 'Medium',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Subtask not found.',
            ]);
    }

    public function test_update_subtask_missing_name()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]), [
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 'In progress',
            'project_task_subtask_priority_level' => 'Medium',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_name');
    }

    public function test_update_subtask_invalid_progress()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]), [
            'project_task_subtask_name' => 'Updated Subtask',
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 123,
            'project_task_subtask_priority_level' => 'Medium',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_progress');
    }

    public function test_update_subtask_invalid_priority_level()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
        ]), [
            'project_task_subtask_name' => 'Updated Subtask',
            'project_task_subtask_description' => 'Updated description',
            'project_task_subtask_progress' => 'In progress',
            'project_task_subtask_priority_level' => 123,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_priority_level');
    }
}
