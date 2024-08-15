<?php

namespace Tests\Feature\ProjectTaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RemoveUserTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $task;

    protected $admin;

    protected $user;

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

        // Assign another user to the task
        $this->user = $this->project->users()->skip(1)->first();
        $this->task->user_id = $this->user->user_id;
        $this->task->save();

        // Login admin user
        Sanctum::actingAs($this->admin);
    }

    public function test_remove_user_from_task_successfully()
    {
        $response = $this->postJson(route('projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(200)
            ->assertJson(['message' => 'User removed from task successfully.']);

        $this->assertDatabaseMissing('project_tasks', [
            'project_task_id' => $this->task->project_task_id,
            'user_id' => $this->user->user_id,
        ]);
    }

    public function test_remove_user_from_task_forbidden_when_not_admin()
    {
        // Create a non-admin user who is part of the project
        $nonAdmin = $this->project->users()->skip(2)->first();
        Sanctum::actingAs($nonAdmin);

        $response = $this->postJson(route('projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_remove_user_from_task_user_not_assigned()
    {
        // Remove the user from the task
        $this->task->user_id = null;
        $this->task->save();

        $response = $this->postJson(route('projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(409)
            ->assertJson(['message' => 'User is not assigned to this task.']);
    }

    public function test_remove_user_from_task_user_not_part_of_project()
    {
        // Create a user who is not part of the project
        $outsideUser = User::factory()->create();

        $response = $this->postJson(route('projects.tasks.users.removeUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'userId' => $outsideUser->user_id,
        ]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'User not found or not associated with the project.']);
    }
}
