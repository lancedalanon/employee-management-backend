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

class UpdateTest extends TestCase
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

    public function testAuthenticatedUserCanUpdateSubtask(): void
    {
        // Arrange form data
        $formData = [
            'project_task_subtask_name' => 'Project task subtask name.',
            'project_task_subtask_description' => 'Project task subtask description.',
            'project_task_subtask_progress' => $this->subtaskProgress,
            'project_task_subtask_priority_level' => $this->subtaskPriorityLevel,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Subtask updated successfully.',
            ]);
        
        // Assert database has the requested leave request
        $this->assertDatabaseHas('project_task_subtasks', [
            'project_task_subtask_id' => $this->projectTaskSubtask->project_task_subtask_id,
            'project_task_id' => $this->projectTask->project_task_id,
            'project_task_subtask_name' => 'Project task subtask name.',
            'project_task_subtask_description' => 'Project task subtask description.',
            'project_task_subtask_progress' => $this->subtaskProgress,
            'project_task_subtask_priority_level' => $this->subtaskPriorityLevel,
        ]);
    }

    public function testAuthenticatedUserFailsToUpdateSubtaskIfSubtaskIsNotFound(): void
    {
        // Arrange form data
        $formData = [
            'project_task_subtask_name' => 'Project task subtask name.',
            'project_task_subtask_description' => 'Project task subtask description.',
            'project_task_subtask_progress' => $this->subtaskProgress,
            'project_task_subtask_priority_level' => $this->subtaskPriorityLevel,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => 99999,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Subtask not found.',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateSubtaskIfNoChangesFound(): void
    {
        // Arrange form data
        $formData = [
            'project_task_subtask_name' => $this->projectTaskSubtask->project_task_subtask_name,
            'project_task_subtask_description' => $this->projectTaskSubtask->project_task_subtask_description,
            'project_task_subtask_progress' => $this->projectTaskSubtask->project_task_subtask_progress,
            'project_task_subtask_priority_level' => $this->projectTaskSubtask->project_task_subtask_priority_level,
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id, 
            'taskId' => $this->projectTask->project_task_id, 
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'No changes detected.',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateSubtaskIfThereAreMissingFields(): void
    {
        // Arrange form data
        $formData = [
            'project_task_subtask_name' => '',
            'project_task_subtask_progress' => '',
            'project_task_subtask_priority_level' => '',
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->projectTask->project_task_id,
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_subtask_name', 
                'project_task_subtask_progress', 
                'project_task_subtask_priority_level',
            ]);
    }

    public function testAuthenticatedUserFailsToUpdateSubtaskIfThereAreInvalidFields(): void
    {
        // Arrange form data
        $longString = str_repeat('a', 256);

        $formData = [
            'project_task_subtask_name' => $longString,
            'project_task_subtask_progress' => 'invalid-task-progress',
            'project_task_subtask_priority_level' => 'invalid-task-priority-level',
        ];

        // Act the response
        $response = $this->putJson(route('v1.projects.tasks.subtasks.update', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->projectTask->project_task_id,
            'subtaskId' => $this->projectTaskSubtask->project_task_subtask_id,
        ]), $formData);

        // Assert the response status and data
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'project_task_subtask_name', 
                'project_task_subtask_progress', 
                'project_task_subtask_priority_level',
            ]);
    }
}
