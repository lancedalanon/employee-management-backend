<?php

namespace Tests\Feature\Admin\AttendanceController;

use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetUserAttendancesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $user;

    protected $dtr;

    protected $adminRole;

    protected $fullTimeRole;

    protected $employeeRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::create(['name' => 'company-admin']);
        $this->fullTimeRole = Role::create(['name' => 'full-time']);
        $this->employeeRole = Role::create(['name' => 'employee']);

        // Create a user and assign roles
        $this->user = User::factory()->create();
        $this->user->assignRole($this->fullTimeRole);
        $this->user->assignRole($this->employeeRole);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($this->adminRole);

        // Specific timestamps for Dtr
        $timeIn = Carbon::parse('2023-07-01 08:00:00');
        $timeOut = Carbon::parse('2023-07-01 18:00:00');

        // Create a Dtr with specific time_in and time_out
        $this->dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $this->user->user_id,
        ]);

        // Authenticate the user admin
        Sanctum::actingAs($this->admin);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_index_with_valid_parameters()
    {
        // Send a GET request with valid query parameters using route name
        $response = $this->getJson(route('companyAdmin.attendances.index', [
            'employment_status' => 'full-time',
            'personnel' => 'employee',
        ]));

        // Assert that the response is successful
        $response->assertStatus(200);

        // You can also assert specific data in the response
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
                ],
            ],
            'first_page_url',
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

    public function test_index_with_missing_parameters()
    {
        // Send a GET request with missing query parameters using route name
        $response = $this->getJson(route('companyAdmin.attendances.index'));

        // Assert that the response returns a 422 Unprocessable Entity status
        $response->assertStatus(422);

        // Optionally, check for specific validation errors
        $response->assertJsonValidationErrors(['employment_status', 'personnel']);
    }
}
