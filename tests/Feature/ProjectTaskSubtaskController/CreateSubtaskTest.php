<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateSubtaskTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        parent::tearDown();
    }

    public function test_create_subtask()
    {
        // Send the POST request to create the subtask
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_progress' => 'Not started',
            'project_task_subtask_priority_level' => 'Low',
        ]);

        // Assert that the subtask was created successfully
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Subtask created successfully.',
                'data' => [
                    'project_task_subtask_name' => 'New Subtask',
                    'project_task_subtask_description' => 'Subtask description',
                    'project_task_subtask_progress' => 'Not started',
                    'project_task_subtask_priority_level' => 'Low',
                ],
            ]);
    }

    public function test_create_subtask_missing_name()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_progress' => 'Not started',
            'project_task_subtask_priority_level' => 'Low',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_name');
    }

    public function test_create_subtask_missing_description()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_progress' => 'Not started',
            'project_task_subtask_priority_level' => 'Low',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_description');
    }

    public function test_create_subtask_missing_progress()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_priority_level' => 'Low',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_progress');
    }

    public function test_create_subtask_missing_priority_level()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_progress' => 'Not started',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_priority_level');
    }

    public function test_create_subtask_with_invalid_progress()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_progress' => 123,
            'project_task_subtask_priority_level' => 'Low',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_progress');
    }

    public function test_create_subtask_with_invalid_priority_level()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
        ]), [
            'project_task_subtask_name' => 'New Subtask',
            'project_task_subtask_description' => 'Subtask description',
            'project_task_subtask_progress' => 'Not started',
            'project_task_subtask_priority_level' => 123,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('project_task_subtask_priority_level');
    }
}
