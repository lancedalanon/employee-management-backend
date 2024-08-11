<?php

namespace Tests\Feature\Admin\AttendanceController\Intern\PartTime;

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
        Role::create(['name' => 'intern']);
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

    public function test_admin_can_view_paginated_part_time_intern_attendance()
    {
        // Create part-time interns with attendance records
        $interns = User::factory(10)->create()->each(function ($user) {
            $user->assignRole('intern');
            $user->assignRole('part-time');
            Dtr::factory(2)->for($user)->withTimeIn(now()->subHours(4))->withTimeOut(now())->create();
        });

        // Make the request to view the first page of part-time interns' attendance
        $perPage = 5;
        $response = $this->getJson(route('admin.attendances.interns.part-time.index', [
            'perPage' => $perPage,
            'page' => 1,
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains the expected data structure
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

        // Check that the response data matches the expected pagination
        $response->assertJson([
            'message' => 'Part time intern attendances retrieved successfully.',
        ]);
    }

    public function test_admin_gets_empty_paginated_list_when_no_part_time_interns_exist()
    {
        // Make the request to view the first page of part-time interns' attendance
        $response = $this->getJson(route('admin.attendances.interns.part-time.index', [
            'perPage' => 5,
            'page' => 1,
        ]));

        // Check that the request was successful
        $response->assertStatus(200);

        // Check that the response contains an empty data array
        $response->assertJson([
            'message' => 'Part time intern attendances retrieved successfully.',
            'data' => [],
            'total' => 0,
        ]);
    }

    public function test_non_admin_cannot_view_part_time_intern_attendance()
    {
        // Create a part-time intern
        $intern = User::factory()->create();
        $intern->assignRole('intern');
        $intern->assignRole('part-time');

        // Authenticate as the part-time intern
        Sanctum::actingAs($intern);

        // Attempt to view the attendance list
        $response = $this->getJson(route('admin.attendances.interns.part-time.index', [
            'perPage' => 5,
            'page' => 1,
        ]));

        // Check that the request is forbidden
        $response->assertStatus(403);
    }
}
