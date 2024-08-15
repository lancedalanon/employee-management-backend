<?php

namespace Tests\Feature\ProjectTaskSubtaskController;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddUserTaskSubtaskTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $task;

    protected $subtask;

    protected $admin;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project with tasks and subtasks
        $this->project = Project::factory()->create();
        $this->task = ProjectTask::factory()->create(['project_id' => $this->project->project_id]);
        $this->subtask = ProjectTaskSubtask::factory()->create([
            'project_task_id' => $this->task->project_task_id,
        ]);

        // Assign the first user as project admin
        $this->admin = User::factory()->create();
        $this->project->users()->attach($this->admin->user_id, ['project_role' => 'project-admin']);

        // Assign another user to the project
        $this->user = User::factory()->create();
        $this->project->users()->attach($this->user->user_id);

        // Login admin user
        Sanctum::actingAs($this->admin);
    }

    public function test_add_user_to_subtask_successfully()
    {
        $response = $this->postJson(route('projects.tasks.subtasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(200)
            ->assertJson(['message' => 'User assigned to subtask successfully.']);

        $this->assertDatabaseHas('project_task_subtasks', [
            'project_task_subtask_id' => $this->subtask->project_task_subtask_id,
            'user_id' => $this->user->user_id,
        ]);
    }

    public function test_add_user_to_subtask_forbidden_when_not_admin()
    {
        // Create a non-admin user who is part of the project
        $nonAdmin = User::factory()->create();
        $this->project->users()->attach($nonAdmin->user_id);
        Sanctum::actingAs($nonAdmin);

        $response = $this->postJson(route('projects.tasks.subtasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_add_user_to_subtask_user_not_part_of_project()
    {
        // Create a user who is not part of the project
        $outsideUser = User::factory()->create();

        $response = $this->postJson(route('projects.tasks.subtasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'userId' => $outsideUser->user_id,
        ]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'User not found or not associated with the project.']);
    }

    public function test_add_user_to_subtask_already_assigned()
    {
        // Assign the user to the subtask initially
        $this->subtask->user_id = $this->user->user_id;
        $this->subtask->save();

        $response = $this->postJson(route('projects.tasks.subtasks.users.addUser', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->task->project_task_id,
            'subtaskId' => $this->subtask->project_task_subtask_id,
            'userId' => $this->user->user_id,
        ]));

        $response->assertStatus(409)
            ->assertJson(['message' => 'User is already assigned to the subtask.']);
    }
}
