<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Testing\ProjectTestingTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectUserControllerTest extends TestCase
{
    use RefreshDatabase, ProjectTestingTrait;

    protected $user;
    protected $project;
    protected $projectUser;
    protected $validRole;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpProject();

        // Create a user and an admin
        $this->user = User::factory()->create();

        // Create a project
        $this->project = Project::factory()->create();

        // Assign the user to the project
        $this->projectUser = ProjectUser::create([
            'project_id' => $this->project->project_id,
            'user_id' => $this->user->user_id,
            'project_role' => config('constants.project_roles')['project-user']
        ]);

        $this->validRole = array_key_first(config('constants.project_roles'));
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
     * Test retrieving users of a project.
     *
     * @return void
     */
    public function test_get_project_users()
    {
        // Create a project with users
        $project = Project::factory()->create();
        $users = User::factory()->count(3)->create();

        // Attach users to the project
        $project->users()->attach($users->pluck('user_id')->toArray());

        // Act: Send a GET request to retrieve users of the project
        $response = $this->getJson(route('projects.users.indexUser', ['projectId' => $project->project_id]));

        // Assert: Check that the request was successful
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'current_page',
                'data' => [
                    '*' => [
                        'user_id',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                        'place_of_birth',
                        'date_of_birth',
                        'gender',
                        'username',
                        'email',
                        'recovery_email',
                        'phone_number',
                        'emergency_contact_name',
                        'emergency_contact_number',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'pivot' => [
                            'project_id',
                            'user_id',
                            'project_role',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
                'first_page_url',
                'last_page_url',
                'prev_page_url',
                'next_page_url',
                'path',
                'per_page',
                'from',
                'to',
                'total',
                'last_page',
            ]);
    }

    /**
     * Test retrieving users of a non-existent project.
     *
     * @return void
     */
    public function test_get_non_existent_project_users()
    {
        // Act: Send a GET request to a non-existent project
        $response = $this->getJson(route('projects.users.indexUser', ['projectId' => 9999]));

        // Assert: Check that the project not found response is returned
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found.',
            ]);
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
        $response = $this->postJson(route('admin.projects.users.storeUser', ['projectId' => $project->project_id]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the users were added successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Users added to project successfully with the specified role.',
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
        $response = $this->postJson(route('admin.projects.users.storeUser', ['projectId' => $nonExistentProjectId]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the response indicates project not found (404)
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project entry not found.',
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
        $response = $this->postJson(route('admin.projects.users.storeUser', ['projectId' => $project->project_id]), ['user_ids' => $invalidUserIds]);

        // Assert: Check that the response indicates validation error (422)
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The selected user_ids.0 is invalid. (and 1 more error)',
                'errors' => [
                    'user_ids.0' => [
                        'The selected user_ids.0 is invalid.',
                    ],
                    'user_ids.1' => [
                        'The selected user_ids.1 is invalid.',
                    ],
                ],
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
                'deleted_at' => null,
            ]);
        }

        // Act: Send a POST request to remove users from the project
        $response = $this->postJson(route('admin.projects.users.destroyUser', ['projectId' => $project->project_id]), [
            'user_ids' => $userIds
        ]);

        // Assert: Check that the users were removed successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Users removed from project successfully.',
            ]);

        // Assert: Check that the users were actually detached from the project
        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('project_users', [
                'project_id' => $project->project_id,
                'user_id' => $userId,
                'deleted_at' => Carbon::now(),
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
        $response = $this->postJson(route('admin.projects.users.destroyUser', ['projectId' => 99999]), [
            'user_ids' => $userIds,
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
        $response = $this->postJson(route('admin.projects.users.destroyUser', ['projectId' => $project->project_id]), [
            'user_ids' => $invalidUserIds, // Invalid user IDs
        ]);

        // Assert: Check that the response indicates validation error (422)
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The selected user_ids.0 is invalid. (and 1 more error)',
                'errors' => [
                    'user_ids.0' => [
                        'The selected user_ids.0 is invalid.',
                    ],
                    'user_ids.1' => [
                        'The selected user_ids.1 is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * Test updating project role successfully.
     *
     * @return void
     */
    public function test_update_project_role_successfully()
    {
        $response = $this->putJson(route('admin.projects.users.updateUser', ['projectId' => $this->project->project_id]), [
            'user_id' => $this->user->user_id,
            'project_role' => $this->validRole,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Project role updated successfully.',
            ]);

        // Verify the role was updated
        $this->assertEquals(config('constants.project_roles')[$this->validRole], $this->projectUser->fresh()->project_role);
    }

    /**
     * Test updating project role with invalid role.
     *
     * @return void
     */
    public function test_update_project_role_with_invalid_role()
    {
        $response = $this->putJson(route('admin.projects.users.updateUser', ['projectId' => $this->project->project_id]), [
            'user_id' => $this->user->user_id,
            'project_role' => 'invalid_role',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The selected project role is invalid.',
                'errors' => [
                    'project_role' => [
                        'The selected project role is invalid.'
                    ]
                ]
            ]);
    }

    /**
     * Test updating project role with invalid user ID.
     *
     * @return void
     */
    public function test_update_project_role_with_invalid_user_id()
    {
        $response = $this->putJson(route('admin.projects.users.updateUser', ['projectId' => $this->project->project_id]), [
            'user_id' => 99999, // Non-existent user ID
            'project_role' => $this->validRole,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The selected user id is invalid.',
                'errors' => [
                    'user_id' => [
                        'The selected user id is invalid.'
                    ]
                ]
            ]);
    }

    /**
     * Test updating project role when user is not part of the project.
     *
     * @return void
     */
    public function test_update_project_role_when_user_not_part_of_project()
    {
        $newUser = User::factory()->create();

        $response = $this->putJson(route('admin.projects.users.updateUser', ['projectId' => $this->project->project_id]), [
            'user_id' => $newUser->user_id,
            'project_role' => $this->validRole,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User is not part of the project.',
            ]);
    }

    /**
     * Test updating project role with missing parameters.
     *
     * @return void
     */
    public function test_update_project_role_with_missing_parameters()
    {
        $response = $this->putJson(route('admin.projects.users.updateUser', ['projectId' => $this->project->project_id]), []);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The user id field is required. (and 1 more error)',
                'errors' => [
                    'user_id' => [
                        'The user id field is required.'
                    ],
                    'project_role' => [
                        'The project role field is required.'
                    ],
                ],
            ]);
    }
}
