<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use App\Models\User;
use App\Testing\DtrTestingTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimeOutControllerTest extends TestCase
{
    use RefreshDatabase, DtrTestingTrait;

    /**
     * Setup method to create a user, Dtr, and DtrBreak.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUserDtrDtrBreak();
    }

    /**
     * Teardown method.
     */
    public function tearDown(): void
    {
        $this->tearDownUserDtrDtrBreak();
        parent::tearDown();
    }

    /**
     * A local helper that creates a new user and assigns roles to them.
     *
     * This function creates a new user using Laravel's User factory, 
     * assigns the user to the 'student', 'full-time', and 'day-shift' roles,
     * and then authenticates the user using Laravel Sanctum.
     *
     * @return \App\Models\User The newly created and authenticated user.
     */
    public function createUserWithRoles()
    {
        // Add a new user
        $user = User::factory()->create();

        // Create roles
        Role::create(['name' => 'student']);
        Role::create(['name' => 'full-time']);
        Role::create(['name' => 'day-shift']);

        // Assign roles to the user
        $user->assignRole('student');
        $user->assignRole('full-time');
        $user->assignRole('day-shift');

        return $user;
    }

    /**
     * Test successful time out.
     */
    public function test_time_out()
    {
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => $images,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Time out recorded successfully.'
            ]);

        $this->assertDatabaseHas('dtrs', [
            'dtr_id' => $dtr->dtr_id,
            'end_of_the_day_report' => 'This is the end of the day report.'
        ]);

        $this->assertCount(2, EndOfTheDayReportImage::where('dtr_id', $dtr->dtr_id)->get());
    }

    /**
     * Test DTR record not found.
     */
    public function test_time_out_dtr_record_not_found()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/dtr/time-out/99999', [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]); // Non-existent DTR ID

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'DTR record not found.'
            ]);
    }

    /**
     * Test time-out already recorded.
     */
    public function test_time_out_already_recorded()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $timeOut = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $user->user_id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Failed to time-out. Record has already been timed out.'
            ]);
    }

    /**
     * Test open break needs to be resumed before timing out.
     */
    public function test_open_break_needs_to_be_resumed_before_timing_out()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        DtrBreak::factory()->withBreakTime(Carbon::now()->subHours(1))->create([
            'dtr_id' => $dtr->dtr_id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You have an open break that needs to be resumed before timing out.'
            ]);
    }

    /**
     * Test total work hours less than 8 hours.
     */
    public function test_time_out_with_insufficient_total_work_hours()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(7);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Insufficient worked hours. You need to work at least 8 hours before timing out for full-time or 4 hours for part-time.'
            ]);
    }

    /**
     * Test validation error for missing end of the day report.
     */
    public function testValidationErrorForMissingReport()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_images' => $images,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_of_the_day_report']);
    }

    /**
     * Test validation error for missing end of the day images.
     */
    public function testValidationErrorForMissingImages()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_of_the_day_images']);
    }

    /**
     * Test validation error for too many images.
     */
    public function testValidationErrorForTooManyImages()
    {
        // Add a new user
        $user = $this->createUserWithRoles();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg'),
            UploadedFile::fake()->image('report3.jpg'),
            UploadedFile::fake()->image('report4.jpg'),
            UploadedFile::fake()->image('report5.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->dtr_id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => $images,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_of_the_day_images']);
    }
}
