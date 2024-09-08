<?php

namespace Tests\Feature\v1\ProjectTaskSubtaskController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $company;
    protected $project;
    protected $subtaskProgress;
    protected $subtaskPriorityLevel;
    protected $projectTask;
    protected $projectTaskSubtask;

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

        // Create dummy project task subtask
        $this->projectTaskSubtask = ProjectTaskSubtask::factory()->create(['project_task_id' => $this->projectTask->project_task_id]);

        $this->subtaskProgress = $this->faker->randomElement(config('constants.project_task_subtask_progress'));
        $this->subtaskPriorityLevel = $this->faker->randomElement(config('constants.project_task_subtask_priority_level'));
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->company = null;
        $this->project = null;
        $this->subtaskProgress = null;
        $this->subtaskPriorityLevel = null;
        $this->projectTask = null;
        $this->projectTaskSubtask = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanAssignUserToSubtask(): void
    {
        // Arrange user
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();
        $user->update(['company_id' => $this->company->company_id]);
        ProjectUser::factory()->create([
            'user_id' => $user->user_id, 
            'company_id' => $this->user->company_id,
            'project_id' => $this->project->project_id,
        ]);

        $formData = [
            'user_id' => $user->user_id,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.subtasks.users.assignUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User assigned to subtask successfully.',
            ]);
        
        // Assert database has the requested leave request
        $this->assertDatabaseHas('project_task_subtasks', [
            'project_task_subtask_id' => $this->projectTaskSubtask->project_task_subtask_id,
            'project_task_id' => $this->projectTask->project_task_id,
            'project_task_subtask_name' => $this->projectTaskSubtask->project_task_subtask_name,
            'project_task_subtask_description' => $this->projectTaskSubtask->project_task_subtask_description,
            'project_task_subtask_progress' => $this->projectTaskSubtask->project_task_subtask_progress,
            'project_task_subtask_priority_level' => $this->projectTaskSubtask->project_task_subtask_priority_level,
            'user_id' => $user->user_id,
        ]);
    }

    public function testAuthenticatedUserFailsToAssignUserToSubtaskIfUserIsNotPartOfTheProject(): void
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
        $response = $this->postJson(route('v1.projects.tasks.subtasks.users.assignUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'User not found in the project.',
            ]);
    }
    
    public function testAuthenticatedUserFailsToAssignUserToSubtaskIfInvalidFields(): void
    {
        // Arrange form data with invalid user_id
        $formData = [
            'user_id' => 99999,
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.subtasks.users.assignUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
            ]);
    }

    public function testAuthenticatedUserFailsToAssignUserToSubtaskIfMissingFields(): void
    {
        // Arrange form data with invalid user_id
        $formData = [
            'user_id' => '',
        ];

        // Act the response
        $response = $this->postJson(route('v1.projects.tasks.subtasks.users.assignUser', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id, 
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
            ]);
    }
}
