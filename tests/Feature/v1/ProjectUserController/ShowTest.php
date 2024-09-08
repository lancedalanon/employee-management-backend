<?php

namespace Tests\Feature\v1\ProjectUserController;

use App\Models\Company;
use App\Models\Project;
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

        // Attach company_id to user
        $this->user->update(['company_id' => $this->company->company_id]);

        Sanctum::actingAs($this->user);

        // Create dummy project
        $this->project = Project::factory()->create();

        ProjectUser::factory()->create([
            'user_id' => $this->user->user_id, 
            'company_id' => $this->user->company_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_admin',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->company = null;
        $this->project = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanRetrieveProjectUserById(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.projects.users.show', [
            'projectId' => $this->project->project_id,
            'userId' => $this->user->user_id,
        ]));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user_id',
                    'project_role',
                    'username',
                    'full_name',
                ]
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Project user retrieved successfully.',
        ]);
    }

    public function testAuthenticatedUserFailsToRetrieveProjectUserByIdIfNotFound(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.projects.users.show', [
            'projectId' => $this->project->project_id,
            'userId' => 99999,
        ]));

        // Assert the response status code
        $response->assertStatus(404);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Project user not found.',
        ]);
    }
}
