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

class BulkAddUsersTest extends TestCase
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

    public function testCompanyAdminCanBulkAddProjectUsers(): void
    {
        // Arrange the user_ids to be added to the project as project user
        $userIds = [
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
        ];

        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkAddUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => $userIds,
        ]));

        // Assert the response and database has the entries
        $response->assertStatus(200)
            ->assertJsonFragment([
               'message' => 'Users assigned to projects successfully.',
            ]);

        // Assert for the first user added
        $this->assertDatabaseHas('project_users', [
            'project_id' => $this->project->project_id,
            'user_id' => $userIds[0],
            'project_role' => 'project_user',
        ]);

        // Assert for the second user added
        $this->assertDatabaseHas('project_users', [
            'project_id' => $this->project->project_id,
            'user_id' => $userIds[1],
            'project_role' => 'project_user',
        ]);
    }

    public function testCompanyAdminCanRestoreSofDeletedProjectUsersInProject(): void
    {
        // Arrange the user_ids to be restored to the project as project user
        $userIds = [
            User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create(['company_id' => $this->company->company_id])->user_id,
        ];

        // Arrange a dummy project users with deleted user
        ProjectUser::factory()->create([
            'user_id' => $userIds[0], 
            'company_id' => $this->companyAdmin->company_id,
            'project_id' => $this->project->project_id,
            'project_role' => 'project_user',
            'deleted_at' => Carbon::now(),
        ]);

        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkAddUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => $userIds,
        ]));

        // Assert the response and database has the entries
        $response->assertStatus(200)
            ->assertJsonFragment([
               'message' => 'Users assigned to projects successfully.',
            ]);

        // Assert for the first user added
        $this->assertDatabaseHas('project_users', [
            'project_id' => $this->project->project_id,
            'user_id' => $userIds[0],
            'project_role' => 'project_user',
            'deleted_at' => null,
        ]);
    }

    public function testCompanyAdminFailsToAddProjectUsersIfMissingRequiredFields(): void
    {
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkAddUsers', [
            'projectId' => $this->project->project_id,
        ]));

        // Assert the response
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_ids']);
    }

    public function testCompanyAdminFailsToAddProjectUsersIfInvalidRequiredFields(): void
    {
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkAddUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => 'invalid_since_this_needs_to_be_an_array',
        ]));

        // Assert the response
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_ids']);
    }

    public function testCompanyAdminFailsToAddProjectUsersIfTheyAreAlreadyPartOfTheProject(): void
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
                'project_role' => 'project_user',
            ]);
        }

        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.users.bulkAddUsers', [
            'projectId' => $this->project->project_id,
            'user_ids' => $userIds,
        ]));

        // Assert the response
        $response->assertStatus(400)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                            'user_id',
                            'username',
                            'first_name',
                            'middle_name',
                            'last_name',
                            'suffix',
                            'full_name',
                            'projects' => [
                                [
                                    'project_id',
                                    'user_id',
                                    'deleted_at',
                                    'pivot' => [
                                        'user_id',
                                        'project_id',
                                        'project_role',
                                        'created_at',
                                        'updated_at',
                                        'deleted_at',
                                    ]
                                ]
                            ]
                        ]
                    ]
            ]);

        $response->assertJsonFragment([
            'message' => 'Some users are already associated with the project and cannot be added again.',
        ]);
    }
}
