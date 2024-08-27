<?php

namespace Tests\Feature\v1\ProjectController;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['company_admin', 'employee', 'full_time', 'day_shift'])->delete();
        $this->companyAdmin = null;
        $this->company = null;

        parent::tearDown();
    }

    public function testCompanyAdminCanCreateProject(): void
    {
        // Arrange project data 
        $projectData = [
            'project_name' => 'Sample Project Name',
        ];
        
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.store', $projectData));

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Project created successfully.',
        ]);
    }

    public function testCompanyAdminFailsToCreateProjectIfMissingRequiredField(): void
    {
        // Arrange project data 
        $projectData = [
            'project_name' => '',
        ];
        
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.store', $projectData));

        // Assert the response
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['project_name']);
    }

    public function testCompanyAdminFailsToCreateProjectIfInvalidField(): void
    {
        // Arrange project_name to have 256 characters
        $longString = str_repeat('a', 256);

        // Arrange project data 
        $projectData = [
            'project_name' => $longString,
        ];
        
        // Act the response
        $response = $this->postJson(route('v1.companyAdmin.projects.store', $projectData));

        // Assert the response
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['project_name']);
    }
}
