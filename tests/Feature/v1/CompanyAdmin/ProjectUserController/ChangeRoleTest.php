<?php

namespace Tests\Feature\v1\CompanyAdmin\ProjectUserController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChangeRoleTest extends TestCase
{
    use RefreshDatabase;
    
    protected $companyAdmin;
    protected $company;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->companyAdmin = User::factory()->withRoles(['company_admin', 'employee', 'full_time', 'day_shift'])->create();

        // Create a dummy company
        $this->company = Company::factory()->create(['user_id' => $this->companyAdmin->user_id]);

        // Attach company_id to company admin user
        $this->companyAdmin->update(['company_id' => $this->company->company_id]);

        Sanctum::actingAs($this->companyAdmin);

        // Create dummy project
        $this->project = Project::factory()->create();

        ProjectUser::factory()->create([
            'user_id' => $this->companyAdmin->user_id, 
            'company_id' => $this->companyAdmin->company_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_admin',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['company_admin', 'employee', 'full_time', 'day_shift'])->delete();
        $this->companyAdmin = null;
        $this->company = null;
        $this->project = null;

        parent::tearDown();
    }

    public function testCompanyAdminCanChangeRoleOfProjectUser(): void
    {
        // Arrange the user by creating a new user instance with specific roles
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])
                            ->create(['company_id' => $this->company->company_id]);

        // Arrange project users table by creating a ProjectUser record for the user
        ProjectUser::factory()->create([
            'user_id' => $user->user_id, // Accessing user_id property correctly
            'company_id' => $this->companyAdmin->company_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_user',
        ]);

        // Act: Perform the role change operation
        $response = $this->putJson(route('v1.companyAdmin.projects.users.changeRole', [
            'projectId' => $this->project->project_id,
            'userId' => $user->user_id, // Correct access to user_id
            'project_role' => 'project_admin',
        ]));

        // Assert the response status and structure
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'User role in the project updated successfully.',
        ]);

        // Assert the database has the updated project user role
        $this->assertDatabaseHas('project_users', [
            'user_id' => $user->user_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_admin',
        ]);
    }

    public function testCompanyAdminFailsToChangeRoleIfProjectUserIsTheCompanyAdminItself(): void
    {
        // Act the response
        $response = $this->putJson(route('v1.companyAdmin.projects.users.changeRole', [
            'projectId' => $this->project->project_id,
            'userId' => $this->companyAdmin->user_id,
            'project_role' => 'project_admin',
        ]));

        // Assert the response
        $response->assertStatus(404);

        // Assert specific data fragments
        $response->assertJsonFragment([
           'message' => 'User not found in the specified project.',
        ]);
    }

    public function testCompanyAdminFailsToChangeRoleIfTHeProjectUserRoleIsSameAsCurrentProjectRole(): void
    {
        // Arrange the user by creating a new user instance with specific roles
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])
                            ->create(['company_id' => $this->company->company_id]);

        // Arrange project users table by creating a ProjectUser record for the user
        ProjectUser::factory()->create([
            'user_id' => $user->user_id, // Accessing user_id property correctly
            'company_id' => $this->companyAdmin->company_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_user',
        ]);

        // Act the response
         $response = $this->putJson(route('v1.companyAdmin.projects.users.changeRole', [
            'projectId' => $this->project->project_id,
            'userId' => $user->user_id,
            'project_role' => 'project_user',
        ]));

        // Assert the response
        $response->assertStatus(422);

        // Assert specific data fragments
        $response->assertJsonFragment([
           'message' => 'User is already assigned to the specified role in this project.',
        ]);
    }
}
