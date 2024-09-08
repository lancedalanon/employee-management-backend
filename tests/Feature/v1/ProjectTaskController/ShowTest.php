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

class ShowTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $company;
    protected $project;
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
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->company = null;
        $this->project = null;
        $this->projectTask = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanRetrieveTaskDataById(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.projects.tasks.show', [
            'projectId' => $this->project->project_id,
            'taskId' => $this->projectTask->project_task_id,
        ]));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'project_task_id',
                    'project_id',
                    'project_task_name',
                    'project_task_description',
                    'project_task_progress',
                    'project_task_priority_level',
                    'user_id',
                    'user',
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Task retrieved successfully.',
        ]);
    }

    public function testAuthenticatedUserFailsToRetrieveTaskDataByIdIfTaskNotFound(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.projects.tasks.show', [
            'projectId' => $this->project->project_id,
            'taskId' => 99999,
        ]));

        // Assert the response status code and data structure
        $response->assertStatus(404)
            ->assertJsonFragment([
            'message' => 'Task not found.',
        ]);
    }
}
