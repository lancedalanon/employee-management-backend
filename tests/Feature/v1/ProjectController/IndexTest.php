<?php

namespace Tests\Feature\v1\ProjectController;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndexTest extends TestCase
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

    public function testAuthenticatedUserCanRetrievePaginatedProjectData(): void
    {
        // Act the response
        $response = $this->getJson(route('v1.projects.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'project_id',
                            'project_name',
                            'project_description',
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'Projects retrieved successfully.',
        ]);
    }

    public function testAuthenticatedUserCanRetrieveEmptyPaginatedProjectData(): void
    {
        // Arrange create a sample user and assign the roles
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();

        // Arrange create a dummy company
        $company = Company::factory()->create(['user_id' => $user->user_id]);

        // Arrange attach company_id to company admin user
        $user->update(['company_id' => $company->company_id]);

        // Arrange authenticate user
        Sanctum::actingAs($user);

        // Act the response
        $response = $this->getJson(route('v1.projects.index'));

        // Assert the response status code and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'project_id',
                            'project_name',
                            'project_description',
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url',
                            'label',
                            'active',
                        ],
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ],
            ]);

        // Assert specific data fragments
        $response->assertJsonFragment([
            'message' => 'No projects found for the provided criteria.',
        ]);
    }
}
