<?php

namespace Tests\Feature\Admin\AttendanceController\Employee\FullTime;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetAttendancesTest extends TestCase
{
    use RefreshDatabase;

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
        Sanctum::actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_access_full_time_employee_attendances()
    {
        // Create some full-time employees with DTR records
        $employee1 = User::factory()->create();
        $employee1->assignRole('employee');
        $employee1->assignRole('full-time');

        $employee2 = User::factory()->create();
        $employee2->assignRole('employee');
        $employee2->assignRole('full-time');

        // Create DTR entries for the employees
        Dtr::factory()->for($employee1)->withTimeIn(now()->subHours(8))->withTimeOut(now())->create();
        Dtr::factory()->for($employee2)->withTimeIn(now()->subHours(9))->withTimeOut(now()->subHours(1))->create();

        // Make the request to the index route
        $response = $this->getJson(route('admin.attendances.employees.full-time.index', [
            'perPage' => 10,
            'page' => 1,
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

        // Check that the message is correct
        $response->assertJson([
            'message' => 'Full time employee attendances retrieved successfully.',
        ]);
    }

    public function test_non_admin_cannot_access_full_time_employee_attendances()
    {
        // Create a non-admin user with employee and full-time roles
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->assignRole('full-time');

        // Authenticate as non-admin
        Sanctum::actingAs($employee);

        // Attempt to access the index route
        $response = $this->getJson(route('admin.attendances.employees.full-time.index', [
            'perPage' => 10,
            'page' => 1,
        ]));

        // Check that the request was forbidden
        $response->assertStatus(403); // Forbidden status code
    }
}
