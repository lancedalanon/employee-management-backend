<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetTasksTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->withUsers(5)->create();
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
        ProjectTask::factory()->count(5)->create(['project_id' => $this->project->project_id]);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        parent::tearDown();
    }

    public function test_returns_tasks_for_existing_project()
    {
        // Hit the endpoint to get tasks for the created project
        $response = $this->getJson(route('projects.tasks.index', ['projectId' => $this->project->project_id]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'current_page',
                'data' => [
                    '*' => [
                        'project_task_id',
                        'project_id',
                        'project_task_name',
                        'project_task_description',
                        'project_task_progress',
                        'project_task_priority_level',
                    ],
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url',
                        'label',
                        'active',
                    ],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }
}
