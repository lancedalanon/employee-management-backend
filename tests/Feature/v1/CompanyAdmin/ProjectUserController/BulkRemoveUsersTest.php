<?php

namespace Tests\Feature\v1\CompanyAdmin\ProjectUserController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkRemoveUsersTest extends TestCase
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

    public function testCompanyAdminCanBulkRemoveProjectUsers(): void
    {
        // Arrange the user_ids to be added to the project as project user
        $userIds = [
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
        ];

        // Arrange project users table by looping through each user_id and create a corresponding ProjectUser record
        foreach ($userIds as $userId) {
            ProjectUser::factory()->create([
                'user_id' => $userId, 
                'company_id' => $this->companyAdmin->company_id,
                'project_id' => $this->project->project_id,
            ]);
        }

        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkRemoveUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => $userIds,
        ]));

        // Assert the response
        $response->assertStatus(200);

        // Assert that the corresponding ProjectUser records have been soft deleted
        $this->assertDatabaseHas('project_users', [
            'project_id' => $this->project->project_id,
            'project_role' => 'project_user',
            'deleted_at' => Carbon::now(),
        ]);
        $this->assertDatabaseHas('project_users', [
            'project_id' => $this->project->project_id,
            'project_role' => 'project_user',
            'deleted_at' => Carbon::now(),
        ]);
    }

    public function testCompanyAdminFailsToRemoveProjectUsersIfMissingRequiredFields(): void
    {
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkRemoveUsers', [
            'projectId' => $this->project->project_id,
        ]));

        // Assert the response
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_ids']);
    }

    public function testCompanyAdminFailsToRemoveProjectUsersIfInvalidRequiredFields(): void
    {
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkRemoveUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => 'invalid_since_this_needs_to_be_an_array',
        ]));

        // Assert the response
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_ids']);
    }

    public function testCompanyAdminFailsToRemoveProjectUsersIfTheyAreNotPartOfTheProject(): void
    {
        // Arrange the user_ids to be removed from the project
        $userIds = [
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
        ];
    
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkRemoveUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => $userIds,
        ]));
    
        // Assert the response
        $response->assertStatus(400)
            ->assertJsonFragment([
            'message' => 'Some users were not found in the specified project and thus were not removed.',
        ]);
    }    
}
