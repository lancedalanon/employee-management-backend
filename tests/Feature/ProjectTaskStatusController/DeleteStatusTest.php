<?php

namespace Tests\Feature\ProjectTaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteStatusTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        $this->status = null;
        parent::tearDown();
    }

    /**
     * Test successful deletion of status.
     *
     * @return void
     */
    public function test_should_delete_status_successfully()
    {
        $deletedStatusId = $this->status->first()->project_task_status_id;

        $response = $this->deleteJson(route('projects.tasks.statuses.deleteStatus', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'id' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Status entry deleted successfully.',
            ]);

        // Assert the status entry was soft deleted
        $this->assertSoftDeleted('project_task_statuses', [
            'project_task_status_id' => $deletedStatusId,
        ]);
    }

    /**
     * Test deletion when task is not found.
     *
     * @return void
     */
    public function test_should_return_404_if_task_not_found()
    {
        $response = $this->deleteJson(route('projects.tasks.statuses.deleteStatus', [
            'projectId' => 999,
            'taskId' => 999,
            'id' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Task not found.',
            ]);
    }

    /**
     * Test deletion when status is not found.
     *
     * @return void
     */
    public function test_should_return_404_if_status_not_found()
    {
        $response = $this->deleteJson(route('projects.tasks.statuses.deleteStatus', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'id' => 999,
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Status not found.',
            ]);
    }
}
