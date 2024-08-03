<?php

namespace Tests\Feature\ProjectTaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->user = $this->project->users()->first();
        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->first()->project_task_id,
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

    public function test_should_delete_status_successfully()
    {
        Sanctum::actingAs($this->user);

        $deletedStatusId = $this->status->first()->project_task_status_id;

        $response = $this->deleteJson(route('projects.tasks.statuses.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Status deleted successfully.',
            ]);

        // Assert the status was soft deleted
        $this->assertSoftDeleted('project_task_statuses', [
            'project_task_status_id' => $deletedStatusId,
        ]);
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

    public function test_should_return_403_if_unauthorized_to_delete_status()
    {
        $newUser = User::factory()->create();
        Sanctum::actingAs($newUser);

        $response = $this->deleteJson(route('projects.tasks.statuses.destroy', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->first()->project_task_id,
            'statusId' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }
}
