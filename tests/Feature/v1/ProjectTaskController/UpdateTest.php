<?php

namespace Tests\Feature\v1\ProjectTaskController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $company;
    protected $project;
    protected $taskProgress;
    protected $taskPriorityLevel;
    protected $projectTask;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();

        // Create a dummy company
        $this->company = Company::factory()->create(['user_id' => $this->user->user_id]);

        // Attach company_id to company admin user
        $this->user->update(['company_id' => $this->company->company_id]);

        Sanctum::actingAs($this->user);

        // Create dummy project
        $this->project = Project::factory()->create();

        ProjectUser::factory()->create([
            'user_id' => $this->user->user_id, 
            'company_id' => $this->user->company_id,
            'project_id' => $this->project->project_id,
        ]);

        // Create dummy project task
        $this->projectTask = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);

        $this->taskProgress = $this->faker->randomElement(config('constants.project_task_progress'));
        $this->taskPriorityLevel = $this->faker->randomElement(config('constants.project_task_priority_level'));
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->company = null;
        $this->project = null;
        $this->taskProgress = null;
        $this->taskPriorityLevel = null;
        $this->projectTask = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanUpdateTask(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Task updated successfully.',
            ]);
        
        // Assert database has the requested leave request
        $this->assertDatabaseHas('project_tasks', [
            'project_task_id' => $this->projectTask->project_task_id,
            'project_id' => $this->project->project_id,
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ]);
    }

    public function testAuthenticatedUserFailsToUpdateTaskIfTaskIsNotFound(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => 99999, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Task not found.',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateTaskIfNoChangesFound(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => $this->projectTask->project_task_name,
            'project_task_description' => $this->projectTask->project_task_description,
            'project_task_progress' => $this->projectTask->project_task_progress,
            'project_task_priority_level' => $this->projectTask->project_task_priority_level,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'No changes detected.',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateTaskIfThereAreMissingFields(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => '',
            'project_task_progress' => '',
            'project_task_priority_level' => '',
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->projectTask->project_task_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_name', 
                'project_task_progress', 
                'project_task_priority_level',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateTaskIfThereAreInvalidFields(): void
    {
        // Arrange form data
        $longString = str_repeat('a', 256);

        $formData = [
            'project_task_name' => $longString,
            'project_task_progress' => 'invalid-task-progress',
            'project_task_priority_level' => 'invalid-task-priority-level',
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->projectTask->project_task_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_name', 
                'project_task_progress', 
                'project_task_priority_level',
            ]);
    }
}
