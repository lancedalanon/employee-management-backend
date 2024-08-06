<?php

namespace Tests\Feature\ProjectTaskSubtaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectTaskSubtaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
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

    public function test_can_update_subtask_status()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id
        ]), [
            'project_task_subtask_status' => 'Completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtask status updated successfully.',
                'data' => [
                    'project_task_subtask_status_id' => $this->subtaskStatus->project_task_subtask_status_id,
                    'project_task_subtask_status' => 'Completed',
                ]
            ]);

        $this->assertDatabaseHas('project_task_subtask_statuses', [
            'project_task_subtask_status_id' => $this->subtaskStatus->project_task_subtask_status_id,
            'project_task_subtask_status' => 'Completed',
        ]);
    }

    public function test_cannot_update_subtask_status_if_not_authorized()
    {
        // Remove the current user from the project to simulate an unauthorized user
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->putJson(route('projects.tasks.subtasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id
        ]), [
            'project_task_subtask_status' => 'Completed',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.'
            ]);
    }

    public function test_cannot_update_non_existent_subtask_status()
    {
        $response = $this->putJson(route('projects.tasks.subtasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => 99999 // Non-existent status ID
        ]), [
            'project_task_subtask_status' => 'Completed',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Subtask status not found.',
            ]);
    }

    public function test_can_update_subtask_status_with_file_upload()
    {
        // Fake file upload
        Storage::fake('public');
        $file = UploadedFile::fake()->image('updated_status_file.jpg');

        $response = $this->putJson(route('projects.tasks.subtasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'subtaskStatusId' => $this->subtaskStatus->project_task_subtask_status_id
        ]), [
            'project_task_subtask_status' => 'Completed',
            'project_task_subtask_status_media_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtask status updated successfully.',
            ]);

        // Assert the new file was uploaded and old file was deleted
        Storage::disk('public')->assertExists('project_task_subtask_status_media_files/' . $file->hashName());

        // Check if the previous file was deleted
        if ($this->subtaskStatus->project_task_subtask_status_media_file) {
            Storage::disk('public')->assertMissing($this->subtaskStatus->project_task_subtask_status_media_file);
        }
    }
}
