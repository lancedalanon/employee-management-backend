<?php

namespace Tests\Feature\v1\CompanyAdmin\ProjectController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateTest extends TestCase
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

    public function testCompanyAdminCanUpdateProject(): void
    {
        // Arrange projects data 
        $projectData = [
            'project_name' => 'Sample Project Name',
        ];
        
        // Act the response
        $response = $this->putJson(route('v1.companyAdmin.projects.update', [
                        'projectId' => $this->project->project_id,
                    ]), $projectData);

        // Assert the response
        $response->assertStatus(200);
        
        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Project updated successfully.',
        ]);
    }

    public function testCompanyAdminFailsToUpdateProjectIfProjectIsNotFound(): void
    {
        // Arrange projects data 
        $projectData = [
            'project_name' => 'Sample Project Name',
        ];
        
        // Act the response
        $response = $this->putJson(route('v1.companyAdmin.projects.update', [
                        'projectId' => 99999,
                    ]), $projectData);

        // Assert the response
        $response->assertStatus(404);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Project not found.',
        ]);
    }

    public function testCompanyAdminFailsToUpdateProjectIfMissingRequiredField(): void
    {
        // Arrange project data 
        $projectData = [
            'project_name' => '',
        ];
        
        // Act the response
        $response = $this->putJson(route('v1.companyAdmin.projects.update', [
                        'projectId' => $this->project->project_id,
                    ]), $projectData);

        // Assert the response
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['project_name']);
    }

    public function testCompanyAdminFailsToUpdateProjectIfInvalidField(): void
    {
        // Arrange project_name to have 256 characters
        $longString = str_repeat('a', 256);

        // Arrange project data 
        $projectData = [
            'project_name' => $longString,
        ];
        
        // Act the response
        $response = $this->putJson(route('v1.companyAdmin.projects.update', [
                        'projectId' => $this->project->project_id,
                    ]), $projectData);

        // Assert the response
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['project_name']);
    }
}
