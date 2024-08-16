<?php

namespace Tests\Feature\Admin\ProjectCompletionController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetUserProjectCompletionByIdTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $project;

    protected $task;

    protected $subtask;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project with 5 users
        $this->project = Project::factory()->withUsers(5)->create();

        // Create a project task associated with the project
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);

        // Retrieve the first user from the project's users as the admin
        $this->admin = $this->project->users()->first();

        // Create roles
        $adminRole = Role::create(['name' => 'company-admin']);
        $fullRole = Role::create(['name' => 'full-time']);
        $employeeRole = Role::create(['name' => 'employee']);

        // Assign the admin role to the first user
        $this->admin->assignRole($adminRole);

        // Assign the employee and full-time roles to all other users, skipping the first user
        $this->project->users()->skip(1)->each(function ($user) use ($employeeRole, $fullRole) {
            $user->assignRole($employeeRole, $fullRole);
        });

        $this->user = $this->project->users()->skip(1)->first();

        // Set the authenticated user for Sanctum
        Sanctum::actingAs($this->admin);

        // Create a subtask associated with the task
        $this->subtask = ProjectTaskSubtask::factory()->create([
            'project_task_id' => $this->task->project_task_id,
        ]);
    }

    public function test_can_retrieve_user_project_completion_within_current_month()
    {
        // Send a GET request to the controller's show method
        $response = $this->getJson(route('companyAdmin.projectCompletions.show', [
            'userId' => $this->user->user_id,
            'employment_status' => 'full-time',
            'personnel' => 'employee',
        ]));

        // Assert the response status is OK (200)
        $response->assertStatus(200);

        // Assert the JSON structure
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user_id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'place_of_birth',
                'date_of_birth',
                'gender',
                'username',
                'email',
                'recovery_email',
                'phone_number',
                'emergency_contact_name',
                'emergency_contact_number',
                'email_verified_at',
                'created_at',
                'updated_at',
                'deleted_at',
                'tasks_not_started_count',
                'tasks_in_progress_count',
                'tasks_reviewing_count',
                'tasks_completed_count',
                'subtasks_not_started_count',
                'subtasks_in_progress_count',
                'subtasks_reviewing_count',
                'subtasks_completed_count',
            ],
        ]);
    }

    public function test_show_with_missing_parameters()
    {
        // Send a GET request with missing query parameters using route name
        $response = $this->getJson(route('companyAdmin.projectCompletions.show', [
            'userId' => $this->user->user_id,
        ]));

        // Assert that the response returns a 422 Unprocessable Entity status
        $response->assertStatus(422);

        // Optionally, check for specific validation errors
        $response->assertJsonValidationErrors(['employment_status', 'personnel']);
    }
}
