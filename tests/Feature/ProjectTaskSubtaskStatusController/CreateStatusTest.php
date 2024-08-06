<?php

namespace Tests\Feature\ProjectTaskSubtaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateStatusTest extends TestCase
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

    public function test_can_create_subtask_status()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id
        ]), [
            'project_task_subtask_status' => 'In Progress',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Subtask status created successfully.',
                'data' => [
                    'project_task_subtask_id' => $this->subtask->project_task_subtask_id,
                    'project_task_subtask_status' => 'In Progress',
                ]
            ]);

        $this->assertDatabaseHas('project_task_subtask_statuses', [
            'project_task_subtask_id' => $this->subtask->project_task_subtask_id,
            'project_task_subtask_status' => 'In Progress',
        ]);
    }

    public function test_cannot_create_subtask_status_if_not_authorized()
    {
        // Remove the current user from the project to simulate an unauthorized user
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->postJson(route('projects.tasks.subtasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id
        ]), [
            'project_task_subtask_status' => 'In Progress',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.'
            ]);
    }

    public function test_cannot_create_subtask_status_with_missing_field()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id
        ]), [
            // Missing 'project_task_subtask_status' field
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_subtask_status' => 'The project task subtask status field is required.',
            ]);
    }

    public function test_can_create_subtask_status_with_file_upload()
    {
        // Fake file upload
        Storage::fake('public');
        $file = UploadedFile::fake()->image('status_file.jpg');

        $response = $this->postJson(route('projects.tasks.subtasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'subtaskId' => $this->subtask->first()->project_task_subtask_id
        ]), [
            'project_task_subtask_status' => 'In Progress',
            'project_task_subtask_status_media_file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Subtask status created successfully.',
            ]);

        // Assert file was uploaded
        Storage::disk('public')->assertExists('project_task_subtask_status_media_files/' . $file->hashName());
    }
}
