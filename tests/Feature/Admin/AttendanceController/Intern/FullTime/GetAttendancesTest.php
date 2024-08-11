<?php

namespace Tests\Feature\Admin\AttendanceController\Intern\FullTime;

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

    public function test_admin_can_index_full_time_intern_attendances()
    {
        Sanctum::actingAs($this->admin);

        // Create several full-time interns with attendance records
        User::factory(10)->create()->each(function ($intern) {
            $intern->assignRole('intern');
            $intern->assignRole('full-time');

            // Create DTR entries for each intern
            Dtr::factory(2)->for($intern)->withTimeIn(now()->subHours(4))->withTimeOut(now())->create();
        });

        // Make the request to the indexInternFullTime route with pagination parameters
        $response = $this->getJson(route('admin.attendances.interns.full-time.index', [
            'perPage' => 5,
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
            'message' => 'Full time intern attendances retrieved successfully.',
        ]);
    }

    public function test_admin_gets_empty_pagination_when_no_interns_found()
    {
        // Make the request to the indexInternFullTime route with pagination parameters
        $response = $this->getJson(route('admin.attendances.interns.full-time.index', [
            'perPage' => 5,
            'page' => 1,
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains an empty data array
        $response->assertJson([
            'message' => 'Full time intern attendances retrieved successfully.',
            'data' => [],
        ]);

        // Check that the total count is 0
        $this->assertEquals(0, $response->json('total'));
    }

    public function test_non_admin_cannot_index_full_time_intern_attendances()
    {
        // Create a full-time intern
        $intern = User::factory()->create();
        $intern->assignRole('intern');
        $intern->assignRole('full-time');

        // Authenticate as non-admin (the intern)
        Sanctum::actingAs($intern);

        // Attempt to access the index route
        $response = $this->getJson(route('admin.attendances.interns.full-time.index', [
            'perPage' => 5,
            'page' => 1,
        ]));

        // Check that the request was forbidden
        $response->assertStatus(403);
    }
}
