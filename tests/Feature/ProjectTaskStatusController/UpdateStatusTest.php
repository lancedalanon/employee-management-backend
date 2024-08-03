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

        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->user = $this->project->users()->first();
        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->first()->project_task_id,
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
        Sanctum::actingAs($this->user);

        $newStatus = 'Updated status';
        $file = UploadedFile::fake()->image('status.jpg');

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
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
        Sanctum::actingAs($this->user);

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_task_status']);
    }

    public function test_should_fail_validation_for_invalid_file_type()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
            'project_task_status' => 'Updated status',
            'project_task_status_media_file' => $file,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_task_status_media_file']);
    }

    public function test_should_return_404_if_status_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => 99999,
        ]), [
            'project_task_status' => 'Updated status',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Status not found.',
            ]);
    }

    public function test_should_return_403_if_unauthorized_to_update_status()
    {
        $newUser = User::factory()->create();
        Sanctum::actingAs($newUser);

        $response = $this->putJson(route('projects.tasks.statuses.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
        ]), [
            'project_task_status' => 'Updated status',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }
}
