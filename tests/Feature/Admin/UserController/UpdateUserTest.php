<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and assign them to variables
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'intern']);
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full-time']);
        Role::create(['name' => 'part-time']);
        Role::create(['name' => 'day-shift']);
        Role::create(['name' => 'afternoon-shift']);
        Role::create(['name' => 'evening-shift']);
        Role::create(['name' => 'early-shift']);
        Role::create(['name' => 'late-shift']);

        // Create an admin user and act as that user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Sanctum::actingAs($this->adminUser);

        // Create a user to be updated
        $this->user = User::factory()->create();
        $this->user->assignRole('intern');
    }

    public function test_it_updates_a_user_successfully()
    {
        $updateData = [
            'first_name' => 'UpdatedFirstName',
            'middle_name' => 'UpdatedMiddleName',
            'last_name' => 'UpdatedLastName',
            'suffix' => 'Jr.',
            'place_of_birth' => 'UpdatedPlaceOfBirth',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
            'username' => 'updatedusername',
            'email' => 'updatedemail@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->putJson(route('admin.users.update', $this->user->user_id), $updateData);

        // Check if the response status is 200
        $response->assertStatus(200);

        // Assert the user was updated with the correct data
        $this->assertDatabaseHas('users', [
            'user_id' => $this->user->user_id,
            'first_name' => 'UpdatedFirstName',
            'middle_name' => 'UpdatedMiddleName',
            'last_name' => 'UpdatedLastName',
            'suffix' => 'Jr.',
            'place_of_birth' => 'UpdatedPlaceOfBirth',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
            'username' => 'updatedusername',
            'email' => 'updatedemail@example.com',
        ]);

        // Assert the password was updated
        $this->assertTrue(Hash::check('newpassword', $this->user->fresh()->password));

        // Assert the roles were updated
        $this->assertTrue($this->user->fresh()->hasRole(['employee', 'full-time', 'day-shift']));
        $this->assertFalse($this->user->fresh()->hasRole('intern')); // Intern role should be removed
    }

    public function test_it_returns_404_if_user_not_found()
    {
        $updateData = [
            'first_name' => 'UpdatedFirstName',
            'middle_name' => 'UpdatedMiddleName',
            'last_name' => 'UpdatedLastName',
            'suffix' => 'Jr.',
            'place_of_birth' => 'UpdatedPlaceOfBirth',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
            'username' => 'updatedusername',
            'email' => 'updatedemail@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'role' => 'employee',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
        ];

        $response = $this->putJson(route('admin.users.update', 99999), $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    public function test_it_validates_update_request()
    {
        $response = $this->putJson(route('admin.users.update', $this->user->user_id), [
            'first_name' => '', // Invalid first name
            'last_name' => '', // Invalid last name
            'username' => 'updatedusername',
            'email' => 'invalid-email', // Invalid email format
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }
}
