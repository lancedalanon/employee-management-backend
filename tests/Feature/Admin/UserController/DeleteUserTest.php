<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\User;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and assign them to variables
        Role::create(['name' => 'intern']);
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'super']);

        // Create an admin user and act as that user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Sanctum::actingAs($this->adminUser);

        // Create a user to be deleted
        $this->user = User::factory()->create();
        $this->user->assignRole('intern');
    }

    public function test_it_deletes_a_user_successfully()
    {
        $response = $this->deleteJson(route('admin.users.destroy', $this->user->user_id));

        // Check if the response status is 200
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User successfully deleted.',
            ]);

        // Assert the user was soft-deleted
        $this->assertSoftDeleted('users', [
            'user_id' => $this->user->user_id,
        ]);
    }

    public function test_it_returns_404_if_user_not_found()
    {
        $response = $this->deleteJson(route('admin.users.destroy', 99999));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    public function test_it_does_not_delete_admin_or_super_users()
    {
        // Create a user with 'admin' role
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        // Attempt to delete the admin user
        $response = $this->deleteJson(route('admin.users.destroy', $adminUser->user_id));

        // Assert that the user is not deleted
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);

        $this->assertDatabaseHas('users', [
            'user_id' => $adminUser->user_id,
        ]);
    }
}
