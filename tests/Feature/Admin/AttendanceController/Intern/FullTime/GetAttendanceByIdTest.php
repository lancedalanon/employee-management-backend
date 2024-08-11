<?php

namespace Tests\Feature\Admin\AttendanceController\Intern\FullTime;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetAttendanceByIdTest extends TestCase
{    
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'intern']);
        Role::create(['name' => 'full-time']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
        Sanctum::actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_show_full_time_intern_attendance()
    {
        // Create a full-time intern with attendance records
        $intern = User::factory()->create();
        $intern->assignRole('intern');
        $intern->assignRole('full-time');

        // Create DTR entries for the intern
        Dtr::factory(2)->for($intern)->withTimeIn(now()->subHours(4))->withTimeOut(now())->create();

        // Make the request to the showInternFullTime route
        $response = $this->getJson(route('admin.attendances.interns.full-time.show', [
            'userId' => $intern->user_id,
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains the expected data
        $response->assertJsonStructure([
            'message',
            'data' => [
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
                'deleted_at',
                'dtr_attendance_count',
                'role',
            ],
        ]);

        // Check that the message is correct
        $response->assertJson([
            'message' => 'Full time intern attendance retrieved successfully.',
        ]);
    }

    public function test_admin_gets_404_when_full_time_intern_not_found()
    {
        // Make the request to the showInternFullTime route with a non-existent user ID
        $response = $this->getJson(route('admin.attendances.interns.full-time.show', [
            'userId' => 99999, // Assuming this ID does not exist
        ]));

        // Check that the request returns a 404 status
        $response->assertStatus(404);

        // Check that the error message is correct
        $response->assertJson([
            'message' => 'User not found.',
        ]);
    }

    public function test_non_admin_cannot_show_full_time_intern_attendance()
    {
        // Create a full-time intern
        $intern = User::factory()->create();
        $intern->assignRole('intern');
        $intern->assignRole('full-time');

        // Authenticate as non-admin (the intern)
        Sanctum::actingAs($intern);

        // Attempt to access the show route
        $response = $this->getJson(route('admin.attendances.interns.full-time.show', [
            'userId' => $intern->user_id,
        ]));

        // Check that the request was forbidden
        $response->assertStatus(403);
    }
}
