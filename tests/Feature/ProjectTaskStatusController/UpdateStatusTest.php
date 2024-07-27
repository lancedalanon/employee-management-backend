<?php

namespace Tests\Feature\ProjectTaskStatusController;


use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $task;
    protected $status;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with known credentials and authenticate
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create a project and task
        $this->project = Project::factory()->create();
        $this->task = ProjectTask::factory()->create([
            'project_id' => $this->project->project_id,
        ]);

        // Create ProjectTaskStatus with the ID of the created ProjectTask
        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->project_task_id,
        ]);

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->status = null;
        parent::tearDown();
    }

    public function test_should_update_status_successfully()
    {
        $newStatus = 'Updated status';
        $file = UploadedFile::fake()->image('status.jpg');

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'statusId' => $this->status->project_task_status_id,
        ]), [
            'project_task_status' => $newStatus,
            'project_task_status_media_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_status_id',
                    'project_task_status',
                    'project_task_id',
                    'project_task_status_media_file',
                    'created_at',
                    'updated_at',
                    'deleted_at'
                ],
            ]);

        Storage::disk('public')->assertExists('project_task_status_media_files/' . $file->hashName());
    }

    public function test_should_fail_validation_for_missing_status()
    {
        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'statusId' => $this->status->project_task_status_id,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_task_status']);
    }

    public function test_should_fail_validation_for_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'statusId' => $this->status->project_task_status_id,
            'project_task_status' => 'Updated status',
            'project_task_status_media_file' => $file,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_task_status_media_file']);
    }

    public function test_should_return_404_for_nonexistent_status()
    {
        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'statusId' => 99999,
        ]), [
            'project_task_status' => 'Updated status',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Status not found.',
            ]);
    }
}
