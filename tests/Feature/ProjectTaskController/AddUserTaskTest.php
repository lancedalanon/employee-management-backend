<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddUserTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $task;

    protected $user;

    protected $admin;

    protected $projectUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project with tasks and users
        $this->project = Project::factory()->withUsers(5)->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);

        // Assign the first user as project admin
        $this->admin = $this->project->users()->first();

        // Ensure the relationship pivot table is updated correctly
        $this->project->users()->updateExistingPivot($this->admin->user_id, [
            'project_role' => 'project-admin',
        ]);

        // Login admin user
        Sanctum::actingAs($this->admin);
    }

    public function test_add_user_to_task_successfully()
    {
        $userId = $this->project->users()->first();

        $response = $this->postJson(route('projects.tasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $userId->user_id,
        ]));

        $response->assertStatus(200)
            ->assertJson(['message' => 'User assigned to task successfully.']);

        $this->assertDatabaseHas('project_tasks', [
            'project_task_id' => $this->task->project_task_id,
            'user_id' => $userId->user_id,
        ]);
    }

    public function test_add_user_to_task_forbidden_when_not_admin()
    {
        // Create a non-admin user who is part of the project
        $nonAdmin = $this->project->users()->skip(2)->first();
        Sanctum::actingAs($nonAdmin);

        $userId = $this->project->users()->first();

        $response = $this->postJson(route('projects.tasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $userId->user_id,
        ]));

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_add_user_to_task_user_not_part_of_project()
    {
        // Create a user who is not part of the project
        $project = Project::factory()->withUsers(5)->create();
        $outsideUserId = $project->users()->first();

        $response = $this->postJson(route('projects.tasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $outsideUserId->user_id,
        ]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'User not found or not associated with the project.']);
    }

    public function test_add_user_to_task_already_assigned()
    {
        // Assign the admin to the task initially
        $this->task->user_id = $this->admin->user_id;
        $this->task->save();

        $response = $this->postJson(route('projects.tasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $this->admin->user_id,
        ]));

        $response->assertStatus(409)
            ->assertJson(['message' => 'User is already assigned to the task.']);
    }
}
