<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Testing\ProjectTestingTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;

class ProjectUserControllerTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

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
     * Test adding users to a project.
     *
     * @return void
     */
    public function test_add_users_to_project()
    {
        // Create a new project for testing
        $project = Project::factory()->create();

        // Generate some user IDs to add to the project
        $userIds = User::factory()->count(3)->create()->pluck('user_id')->toArray();

        // Act: Send a POST request to add users to the project
        $response = $this->postJson(route('admin.projects.addUsersToProject', ['projectId' => $project->project_id]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the users were added successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Users added to project successfully.',
            ]);

        // Assert: Check that the users were actually attached to the project
        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('project_users', [
                'project_id' => $project->project_id,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Test adding users to a non-existent project.
     *
     * @return void
     */
    public function test_add_users_to_non_existent_project()
    {
        // Generate some user IDs to add to the project (can be any valid user IDs)
        $userIds = User::factory()->count(3)->create()->pluck('user_id')->toArray();

        // Define a non-existent project ID
        $nonExistentProjectId = 9999;

        // Act: Send a POST request to add users to the project
        $response = $this->postJson(route('admin.projects.addUsersToProject', ['projectId' => $nonExistentProjectId]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the response indicates project not found (404)
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found.',
            ]);
    }

    /**
     * Test adding users with invalid user IDs.
     *
     * @return void
     */
    public function test_add_users_with_invalid_user_ids()
    {
        // Create a new project for testing
        $project = Project::factory()->create();

        // Define some invalid user IDs (non-existent IDs)
        $invalidUserIds = ['asd', 'asd'];

        // Define the endpoint and request payload
        $response = $this->postJson(route('admin.projects.addUsersToProject', ['projectId' => $project->project_id]), ['user_ids' => $invalidUserIds]);

        // Assert: Check that the response indicates validation error (422)
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation error.',
            ]);
    }

    /**
     * Test removing users from a project.
     *
     * @return void
     */
    public function test_remove_users_from_project()
    {
        // Create a new project for testing
        $project = Project::factory()->create();

        // Generate some user IDs and attach them to the project
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('user_id')->toArray();
        $project->users()->attach($userIds);

        // Verify users are attached to the project
        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('project_users', [
                'project_id' => $project->project_id,
                'user_id' => $userId,
            ]);
        }

        // Act: Send a POST request to remove users from the project
        $response = $this->postJson(route('admin.projects.removeUsersFromProject', ['projectId' => $project->project_id]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the users were removed successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Users removed from project successfully.',
            ]);

        // Assert: Check that the users were actually detached from the project
        foreach ($userIds as $userId) {
            $this->assertDatabaseMissing('project_users', [
                'project_id' => $project->project_id,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Test removing users from a non-existent project.
     *
     * @return void
     */
    public function test_remove_users_from_non_existent_project()
    {
        // Create a new project for testing
        $project = Project::factory()->create();

        // Generate some user IDs and attach them to the project
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('user_id')->toArray();
        $project->users()->attach($userIds);

        // Act: Send a POST request to remove users from a non-existent project
        $response = $this->postJson(route('admin.projects.removeUsersFromProject', ['projectId' => 'invalidId']), [
            'user_ids' => $userIds, // Providing some user_ids, though the project doesn't exist
        ]);

        // Assert: Check that the response status is 404 (Not Found)
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found.',
            ]);
    }

    /**
     * Test removing users with invalid user IDs.
     *
     * @return void
     */
    public function test_remove_users_with_invalid_user_ids()
    {
        // Create a new project for testing
        $project = Project::factory()->create();

        // Define some invalid user IDs (non-existent IDs)
        $invalidUserIds = ['abc', 'def'];

        // Act: Send a POST request to remove users with invalid user IDs from the project
        $response = $this->postJson(route('admin.projects.removeUsersFromProject', ['projectId' => $project->project_id]), [
            'user_ids' => $invalidUserIds, // Invalid user IDs
        ]);

        // Assert: Check that the response status is 422 (Unprocessable Entity)
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation error.',
            ]);
    }
}
