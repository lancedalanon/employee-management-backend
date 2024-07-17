<?php

namespace Tests\Feature\ProjectController;

use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    protected $project;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        $this->tearDownProject();
        parent::tearDown();
    }

    /**
     * Test creating a new project with valid data.
     *
     * @return void
     */
    public function test_create_project_with_valid_data()
    {
        // Valid project data
        $data = [
            'project_name' => 'New Project',
            'project_description' => 'This is a test project.',
        ];

        // Send a POST request to create a project
        $response = $this->postJson(route('admin.projects.createProject'), $data);

        // Assert that the response is successful (201 Created)
        $response->assertStatus(201);

        // Assert that the JSON response matches the created project structure
        $response->assertJsonStructure([
            'project_id',
            'project_name',
            'project_description',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * Test creating a new project with invalid data (validation error).
     *
     * @return void
     */
    public function test_create_project_with_invalid_data()
    {
        // Invalid project data (missing project_name)
        $data = [
            'project_description' => 'This is a test project.',
        ];

        // Send a POST request to create a project
        $response = $this->postJson(route('admin.projects.createProject'), $data);

        // Assert that the response status is 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the JSON response contains the validation error message
        $response->assertJsonValidationErrors('project_name');
    }

    /**
     * Test creating a new project with unexpected error.
     *
     * @return void
     */
    public function test_create_project_with_validation_error()
    {
        // Force a new project with project_name validation error
        $data = [
            'project_name' => str_repeat('a', 300),
            'project_description' => 'This is a test project.',
        ];

        // Send a POST request to create a project
        $response = $this->postJson(route('admin.projects.createProject'), $data);

        // Assert that the response status is 422
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_name']);
    }
}
