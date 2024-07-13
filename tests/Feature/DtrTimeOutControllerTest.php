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
use Tests\TestCase;

class DtrTimeOutControllerTest extends TestCase
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
     * Test successful time out.
     */
    public function testTimeOut()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => $images,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Time out recorded successfully.'
            ]);

        $this->assertDatabaseHas('dtrs', [
            'id' => $dtr->id,
            'end_of_the_day_report' => 'This is the end of the day report.'
        ]);

        $this->assertCount(2, EndOfTheDayReportImage::where('dtr_id', $dtr->id)->get());
    }

    /**
     * Test DTR record not found.
     */
    public function testTimeOutDtrRecordNotFound()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/dtr/time-out/99999', [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]); // Non-existent DTR ID

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'DTR record not found.'
            ]);
    }

    /**
     * Test time-out already recorded.
     */
    public function testTimeOutAlreadyRecorded()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $timeOut = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->withTimeOut($timeOut)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to time-out. Record has already been timed out.'
            ]);
    }

    /**
     * Test open break needs to be resumed before timing out.
     */
    public function testOpenBreakNeedsToBeResumedBeforeTimingOut()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        DtrBreak::factory()->withBreakTime(Carbon::now()->subHours(1))->create([
            'dtr_id' => $dtr->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have an open break that needs to be resumed before timing out.'
            ]);
    }

    /**
     * Test total work hours less than 8 hours.
     */
    public function testTimeOutTotalWorkHoursLessThanEight()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(7);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => [
                UploadedFile::fake()->image('report1.jpg')
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You need to work at least 8 hours before timing out.'
            ]);
    }

    /**
     * Test validation error for missing end of the day report.
     */
    public function testValidationErrorForMissingReport()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
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
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
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
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now()->subHours(8);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->id,
        ]);

        $images = [
            UploadedFile::fake()->image('report1.jpg'),
            UploadedFile::fake()->image('report2.jpg'),
            UploadedFile::fake()->image('report3.jpg'),
            UploadedFile::fake()->image('report4.jpg'),
            UploadedFile::fake()->image('report5.jpg')
        ];

        $response = $this->postJson('/api/dtr/time-out/' . $dtr->id, [
            'end_of_the_day_report' => 'This is the end of the day report.',
            'end_of_the_day_images' => $images,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_of_the_day_images']);
    }
}
