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

class StoreTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $company;
    protected $project;
    protected $taskProgress;
    protected $taskPriorityLevel;

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

        parent::tearDown();
    }

    public function testAuthenticatedUserCanAddTask(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.store', $this->project->project_id), $formData);

        // Assert the response status and data
        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Task created successfully.',
            ]);
        
        // Assert database has the requested leave request
        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $this->project->project_id,
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ]);
    }

    public function testAuthenticatedUserFailsToAddTaskIfTheUserIsNotPartOfTheProject(): void
    {
        // Arrange a new sample user and assign the roles
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();

        // Arrange a new dummy company
        $this->company = Company::factory()->create(['user_id' => $user->user_id]);

        // Attach company_id to company admin user
        $user->update(['company_id' => $this->company->company_id]);

        Sanctum::actingAs($user);

        // Arrange form data
        $formData = [
            'project_task_name' => 'Project task name.',
            'project_task_description' => 'Project task description.',
            'project_task_progress' => $this->taskProgress,
            'project_task_priority_level' => $this->taskPriorityLevel,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.store', $this->project->project_id), $formData);

        // Assert the response status and data
        $response->assertStatus(409)
            ->assertJsonFragment([
                'message' => 'You are not part of the project.',
            ]);
    }

    public function testAuthenticatedUserFailsToAddTaskIfThereAreMissingFields(): void
    {
        // Arrange form data
        $formData = [
            'project_task_name' => '',
            'project_task_progress' => '',
            'project_task_priority_level' => '',
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.store', $this->project->project_id), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_name', 
                'project_task_progress', 
                'project_task_priority_level',
            ]);
    }

    public function testAuthenticatedUserFailsToAddTaskIfThereAreInvalidFields(): void
    {
        // Arrange form data
        $longString = str_repeat('a', 256);

        $formData = [
            'project_task_name' => $longString,
            'project_task_progress' => 'invalid-task-progress',
            'project_task_priority_level' => 'invalid-task-priority-level',
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.store', $this->project->project_id), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_name', 
                'project_task_progress', 
                'project_task_priority_level',
            ]);
    }
}
