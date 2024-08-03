<?php

namespace Tests\Feature\ProjectTaskStatusController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetStatusesTest extends TestCase
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
        Sanctum::actingAs($this->user);

        $this->status = ProjectTaskStatus::factory()->create([
            'project_task_id' => $this->task->first()->project_task_id,
        ]);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->project = null;
        $this->task = null;
        parent::tearDown();
    }

    public function test_can_get_paginated_statuses_for_a_task()
    {
        // Send a GET request to the endpoint with pagination query parameters
        $response = $this->getJson(route('projects.tasks.statuses.index', [
            'projectId' => $this->project->first()->project_id,
            'taskId' => $this->task->first()->project_task_id,
        ]));

        // Assert the response status is 200
        $response->assertStatus(200);

        // Assert the response contains the correct keys and data
        $response->assertJsonStructure([
            'message',
            'current_page',
            'data' => [
                '*' => [
                    'project_task_id',
                    'project_task_status',
                    'project_task_status_media_file',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    public function test_returns_empty_when_no_statuses()
    {
        // Create a new task without statuses
        $taskWithoutStatuses = ProjectTask::factory()->create([
            'project_id' => $this->project->project_id,
        ]);

        // Send a GET request to the endpoint with the new task ID
        $response = $this->getJson(route('projects.tasks.statuses.index', [
            'projectId' => $this->project->project_id,
            'taskId' => $taskWithoutStatuses->project_task_id,
        ]));

        // Assert the response status is 200
        $response->assertStatus(200);

        // Assert the response contains the correct structure and empty data
        $response->assertJson([
            'message' => 'Statuses retrieved successfully.',
            'data' => [],
        ]);
    }

    public function test_should_return_403_if_unauthorized_to_get_statuses()
    {
        $response = $this->getJson(route('projects.tasks.statuses.index', [
            'projectId' => $this->project->project_id,
            'taskId' => 99999,
            'statusId' => $this->status->first()->project_task_status_id,
        ]));

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }
}
