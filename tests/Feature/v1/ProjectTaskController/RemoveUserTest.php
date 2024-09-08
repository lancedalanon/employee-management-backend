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

class RemoveUserTest extends TestCase
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

        $this->taskProgress = $this->faker->randomElement(config('constants.project_task_progress'));
        $this->taskPriorityLevel = $this->faker->randomElement(config('constants.project_task_priority_level'));

        // Create dummy project task
        $this->projectTask = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
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

    public function testAuthenticatedUserCanRemoveUserToProjectTask(): void
    {
        // Arrange that user is assigned to the task
        $this->projectTask->update(['user_id' => $this->user->user_id]);

        // Arrange user data form
        $formData = [
            'user_id' => $this->user->user_id,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User removed from task successfully.',
            ]);
    }

    public function testAuthenticatedUserFailsToRemoveUserToProjectTaskIfUserIsNotPartOfTheProject(): void
    {
        // Arrange user
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();
        $company = Company::factory()->create(['user_id' => $user->user_id]);
        $user->update(['company_id' => $company->company_id]);
        $project = Project::factory()->create();
        ProjectUser::factory()->create([
            'user_id' => $user->user_id, 
            'company_id' => $user->company_id,
            'project_id' => $project->project_id,
        ]);

        $formData = [
            'user_id' => $user->user_id,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'User not found in the project.',
            ]);
    }

    public function testAuthenticatedUserFailsToRemoveUserToProjectTaskIfUserHasNotBeenAssigned(): void
    {
        // Arrange user
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();
        $user->update(['company_id' => $this->company->company_id]);
        ProjectUser::factory()->create([
            'user_id' => $user->user_id, 
            'company_id' => $user->company_id,
            'project_id' => $this->project->project_id,
        ]);

        // Arrange user data
        $formData = [
            'user_id' => $user->user_id,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(409)
            ->assertJsonFragment([
                'message' => 'User has not been assigned.',
            ]);
    }

    public function testAuthenticatedUserFailsToAssignUserToProjectTaskIfInvalidFields(): void
    {
        // Arrange form data with invalid user_id
        $formData = [
            'user_id' => 99999,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
            ]);
    }

    public function testAuthenticatedUserFailsToAssignUserToProjectTaskIfMissingFields(): void
    {
        // Arrange form data with invalid user_id
        $formData = [
            'user_id' => '',
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
            ]);
    }
}
