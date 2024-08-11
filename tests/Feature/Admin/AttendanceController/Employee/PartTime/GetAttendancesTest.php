<?php

namespace Tests\Feature\Admin\AttendanceController\Employee\PartTime;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetAttendancesTest extends TestCase
{
    use RefreshDatabase; // Refresh the database after each test

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'part-time']);
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

    public function test_admin_can_index_part_time_employee_attendances()
    {
        // Create part-time employees with attendance records
        $employees = User::factory(10)->create()->each(function ($employee) {
            $employee->assignRole('employee');
            $employee->assignRole('part-time');

            // Create DTR entries for the employee
            Dtr::factory()->for($employee)->withTimeIn(now()->subHours(4))->withTimeOut(now())->create();
        });

        // Set pagination parameters
        $perPage = 5;
        $page = 1;

        // Make the request to the indexEmployeePartTime route
        $response = $this->getJson(route('admin.attendances.employees.part-time.index', [
            'perPage' => $perPage,
            'page' => $page
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains the expected data
        $response->assertJsonStructure([
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
                    'deleted_at',
                    'dtr_attendance_count',
                    'role',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    public function test_admin_gets_empty_list_when_no_part_time_employees_exist()
    {
        // Set pagination parameters
        $perPage = 5;
        $page = 1;

        // Make the request to the indexEmployeePartTime route
        $response = $this->getJson(route('admin.attendances.employees.part-time.index', [
            'perPage' => $perPage,
            'page' => $page
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains the expected data
        $response->assertJson([
            'message' => 'Part time employee attendances retrieved successfully.',
            'data' => [],
            'total' => 0,
        ]);
    }

    public function test_non_admin_cannot_index_part_time_employee_attendances()
    {
        // Create a part-time employee
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->assignRole('part-time');

        // Authenticate as non-admin (the employee)
        Sanctum::actingAs($employee);

        // Set pagination parameters
        $perPage = 5;
        $page = 1;

        // Attempt to access the index route
        $response = $this->getJson(route('admin.attendances.employees.part-time.index', [
            'perPage' => $perPage,
            'page' => $page
        ]));

        // Check that the request was forbidden
        $response->assertStatus(403);
    }
}
