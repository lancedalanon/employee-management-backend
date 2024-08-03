<?php

namespace Tests\Feature\ProjectTaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateStatusTest extends TestCase
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

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        parent::tearDown();
    }

    /**
     * Test successful creation of status with a file upload.
     *
     * @return void
     */
    public function test_should_create_status_successfully_with_file(): void
    {
        $file = UploadedFile::fake()->image('status_image.jpg');

        $response = $this->postJson(route('projects.tasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
        ]), [
            'project_task_status' => 'In progress',
            'project_task_status_media_file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_id',
                    'project_task_status',
                    'project_task_status_media_file',
                ],
            ]);

        // Assert the status entry was created in the database
        $this->assertDatabaseHas('project_task_statuses', [
            'project_task_id' => $this->task->project_task_id,
            'project_task_status' => 'In progress',
        ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('project_task_status_media_files/' . $file->hashName());
    }

    /**
     * Test creation of status with validation errors.
     *
     * @return void
     */
    public function test_should_return_validation_error_for_invalid_data(): void
    {
        $response = $this->postJson(route('projects.tasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
        ]), [
            'project_task_status' => '', // Invalid: required field
            'project_task_status_media_file' => 'invalid_file',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_task_status', 'project_task_status_media_file']);
    }

    /**
     * Test creation of status when task is not found.
     *
     * @return void
     */
    public function test_should_return_403_if_unauthorized_to_create_status(): void
    {
        $file = UploadedFile::fake()->image('status_image.jpg');

        $response = $this->post(route('projects.tasks.statuses.store', [
            'projectId' => $this->project->project_id,
            'taskId' => 99999,
        ]), [
            'project_task_status' => 'Complete',
            'project_task_status_media_file' => $file,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }
}
