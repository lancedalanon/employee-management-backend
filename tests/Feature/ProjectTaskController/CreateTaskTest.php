<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $project;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with known credentials and authenticate
        $this->user = User::factory()->create();
        $this->project = Project::factory()->withUsers(5)->create();
        $this->user = $this->project->users()->first();
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        parent::tearDown();
    }

    /**
     * Test creating a task with valid data.
     */
    public function test_create_task_with_valid_data(): void
    {
        // Data for the task creation
        $taskData = [
            'project_task_name' => 'Test Task',
            'project_task_description' => 'This is a test task description.',
            'project_task_progress' => 'Not started',
            'project_task_priority_level' => 'Medium',
        ];

        // Send a POST request to create the task
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);

        // Assert that the response status is 201 (Created)
        $response->assertStatus(201);

        // Assert that the response contains the correct message and data
        $response->assertJson([
            'message' => 'Task created successfully.',
            'data' => [
                'project_task_name' => $taskData['project_task_name'],
                'project_task_description' => $taskData['project_task_description'],
                'project_task_progress' => $taskData['project_task_progress'],
                'project_task_priority_level' => $taskData['project_task_priority_level'],
                'project_id' => $this->project->project_id,
            ],
        ]);

        // Assert that the task was actually created in the database
        $this->assertDatabaseHas('project_tasks', [
            'project_task_name' => $taskData['project_task_name'],
            'project_task_description' => $taskData['project_task_description'],
            'project_task_progress' => $taskData['project_task_progress'],
            'project_task_priority_level' => $taskData['project_task_priority_level'],
            'project_id' => $this->project->project_id,
        ]);
    }

    /**
     * Test creating a task with missing project_task_name.
     */
    public function test_create_task_without_project_task_name(): void
    {
        $taskData = [
            'project_task_description' => 'This is a test task description.',
            'project_task_progress' => 'Not started',
            'project_task_priority_level' => 'Medium',
        ];
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_task_name']);
    }

    /**
     * Test creating a task with missing project_task_description.
     */
    public function test_create_task_without_project_task_description(): void
    {
        $taskData = [
            'project_task_name' => 'Test Task',
            'project_task_progress' => 'Not started',
            'project_task_priority_level' => 'Medium',
        ];
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_task_description']);
    }

    /**
     * Test creating a task with invalid project_task_progress.
     */
    public function test_create_task_with_invalid_project_task_progress(): void
    {
        $taskData = [
            'project_task_name' => 'Test Task',
            'project_task_description' => 'This is a test task description.',
            'project_task_progress' => 'Unknown',
            'project_task_priority_level' => 'Medium',
        ];
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_task_progress']);
    }

    /**
     * Test creating a task with invalid project_task_priority_level.
     */
    public function test_create_task_with_invalid_project_task_priority_level(): void
    {
        $taskData = [
            'project_task_name' => 'Test Task',
            'project_task_description' => 'This is a test task description.',
            'project_task_progress' => 'Not started',
            'project_task_priority_level' => 'Unknown',
        ];
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_task_priority_level']);
    }

    /**
     * Test creating a task with all invalid data.
     */
    public function test_create_task_with_all_invalid_data(): void
    {
        $taskData = [
            'project_task_name' => '', // Invalid: required field
            'project_task_description' => '', // Invalid: required field
            'project_task_progress' => 'Unknown', // Invalid: not in the list
            'project_task_priority_level' => 'Unknown', // Invalid: not in the list
        ];
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $this->project->project_id]), $taskData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_task_name',
            'project_task_description',
            'project_task_progress',
            'project_task_priority_level',
        ]);
    }

    /**
     * Test creating a task for a non-existent project.
     */
    public function test_create_task_for_non_existent_project(): void
    {
        // Non-existent project ID
        $nonExistentProjectId = 9999;

        // Data for the task creation
        $taskData = [
            'project_task_name' => 'Test Task',
            'project_task_description' => 'This is a test task description.',
            'project_task_progress' => 'Not started',
            'project_task_priority_level' => 'Medium',
        ];

        // Send a POST request to create the task
        $response = $this->postJson(route('projects.tasks.store', ['projectId' => $nonExistentProjectId]), $taskData);

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert that the response contains the correct message
        $response->assertJson([
            'message' => 'Project not found.',
        ]);
    }
}
