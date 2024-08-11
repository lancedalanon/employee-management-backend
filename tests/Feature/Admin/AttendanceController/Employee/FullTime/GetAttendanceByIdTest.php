<?php

namespace Tests\Feature\Admin\AttendanceController\Employee\FullTime;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetAttendanceByIdTest extends TestCase
{
    use RefreshDatabase; // Refresh the database after each test

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full-time']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        // Authenticate as admin
        Sanctum::actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_show_full_time_employee_attendance()
    {
        // Create a full-time employee with attendance records
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->assignRole('full-time');

        // Create DTR entries for the employee
        Dtr::factory()->for($employee)->withTimeIn(now()->subHours(8))->withTimeOut(now())->create();

        // Make the request to the showEmployeeFullTime route
        $response = $this->getJson(route('admin.attendances.employees.full-time.show', ['userId' => $employee->user_id]));

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
            'message' => 'Full time employee attendance retrieved successfully.',
        ]);
    }

    public function test_admin_gets_404_for_non_existent_employee()
    {
        // Attempt to access the show route with a non-existent user ID
        $response = $this->getJson(route('admin.attendances.employees.full-time.show', ['userId' => 99999]));

        // Check that the request returns a 404 status code
        $response->assertStatus(404);
    }

    public function test_non_admin_cannot_show_full_time_employee_attendance()
    {
        // Create a full-time employee
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->assignRole('full-time');

        // Authenticate as non-admin (the employee)
        Sanctum::actingAs($employee);

        // Attempt to access the show route
        $response = $this->getJson(route('admin.attendances.employees.full-time.show', ['userId' => $employee->user_id]));

        // Check that the request was forbidden
        $response->assertStatus(403);
    }
}
