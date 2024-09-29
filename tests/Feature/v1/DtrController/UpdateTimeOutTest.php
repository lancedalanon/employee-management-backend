<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Company;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateTimeOutTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $dtr;
    protected $companyAdmin;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the current date and set the time to 08:00:00
        $timeNowAdjusted = Carbon::now()->setTime(8, 0, 0);
        Carbon::setTestNow($timeNowAdjusted);

        // Create roles
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->companyAdmin = User::factory()->withRoles(['company_admin', 'employee', 'full_time', 'day_shift'])->create();

        // Create a dummy company
        $this->company = Company::factory()->create(['user_id' => $this->companyAdmin->user_id]);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create([
            'company_id' => $this->company->company_id,
        ]);

        // Create a sample DTR record for the user with a time-in event
        $this->dtr = Dtr::factory()->withTimeIn()->create(['user_id' => $this->user->user_id, 'dtr_time_in' => Carbon::now()]);

        Sanctum::actingAs($this->user);

        // Set up fake storage disk
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->dtr = null;
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testAuthenticatedUserCanUpdateTimeOut(): void
    {
        // Arrange fake image for DTR time out
        $fakeImage = UploadedFile::fake()->image('dtr_time_out_image.jpg', 600, 600);
    
        // Arrange multiple fake images for the end of the day report
        $multipleFakeImages = [
            UploadedFile::fake()->image('image1.jpg', 600, 600),
            UploadedFile::fake()->image('image2.jpg', 600, 600),
            UploadedFile::fake()->image('image3.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
        ];

        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => $fakeImage,
            'dtr_end_of_the_day_report' => 'The day has ended.',
            'end_of_the_day_report_images' => $multipleFakeImages,
            'dtr_reason_of_late_entry' => 'The entry was late because of a late turn over.',
        ]);
    
        // Assert that the response has the correct status and message
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Timed out successfully.',
            ]);
    
        // Assert that the DTR record in the database is updated correctly
        $this->assertDatabaseHas('dtrs', [
            'user_id' => $this->user->user_id,
            'dtr_end_of_the_day_report' => 'The day has ended.',
            'dtr_reason_of_late_entry' => 'The entry was late because of a late turn over.',
        ]);
    }    

    public function testAuthenticatedUserFailsTimeOutWithMissingField(): void
    {
        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => '',
            'dtr_end_of_the_day_report' => '',
            'end_of_the_day_report_images' => '',
            'dtr_reason_of_late_entry' => '',
        ]);
    
        // Assert that the response has the correct status and message
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'dtr_time_out_image',
                'dtr_end_of_the_day_report',
                'end_of_the_day_report_images',
            ]);
    }

    public function testAuthenticatedUserFailsTimeOutWithInvalidFields(): void
    {
        // Assert string with 256 characters
        $longReport = str_repeat('a', 256);

        // Arrange an invalid file type
        $invalidFile = UploadedFile::fake()->create('invalid_file.txt', 100, 'text/plain');

        // Arrange 5 fake images for the end of the day report
        $invalidMultipleFakeImages = [
            UploadedFile::fake()->image('image1.jpg', 600, 600),
            UploadedFile::fake()->image('image2.jpg', 600, 600),
            UploadedFile::fake()->image('image3.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
        ];

        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => $invalidFile,
            'dtr_end_of_the_day_report' => $longReport,
            'end_of_the_day_report_images' => $invalidMultipleFakeImages,
            'dtr_reason_of_late_entry' => $longReport,

        ]);
    
        // Assert that the response has the correct status and message
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'dtr_time_out_image',
                'dtr_end_of_the_day_report',
                'end_of_the_day_report_images',
                'dtr_reason_of_late_entry',
            ]);
    }

    public function testAuthenticatedUserFailsTimeOutIfNoTimeInSet(): void
    {
        // Create a sample user and assign the roles
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);

        // Arrange fake image for DTR time out
        $fakeImage = UploadedFile::fake()->image('dtr_time_out_image.jpg', 600, 600);

        // Arrange multiple fake images for the end of the day report
        $multipleFakeImages = [
            UploadedFile::fake()->image('image1.jpg', 600, 600),
            UploadedFile::fake()->image('image2.jpg', 600, 600),
            UploadedFile::fake()->image('image3.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
        ];

        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => $fakeImage,
            'dtr_end_of_the_day_report' => 'The day report is here.',
            'end_of_the_day_report_images' => $multipleFakeImages,
            'dtr_reason_of_late_entry' => 'The entry was late because of a late turn over.',
        ]);

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Failed to time out. You have not timed in yet.',
            ]);
    }

    public function testAuthenticatedUserFailsTimeOutIfThereIsOpenBreak(): void
    {
        // Create a sample user and assign the roles
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);

        // Arrange fake image for DTR time out
        $fakeImage = UploadedFile::fake()->image('dtr_time_out_image.jpg', 600, 600);

        // Arrange multiple fake images for the end of the day report
        $multipleFakeImages = [
            UploadedFile::fake()->image('image1.jpg', 600, 600),
            UploadedFile::fake()->image('image2.jpg', 600, 600),
            UploadedFile::fake()->image('image3.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
        ];

        // Create a DTR record
        $dtr = Dtr::factory()->withTimeIn()->create(['user_id' => $user->user_id]);

        // Create a break record
        DtrBreak::factory()->onlyBreakTime()->create(['dtr_id' => $dtr->dtr_id]);

        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => $fakeImage,
            'dtr_end_of_the_day_report' => 'The day report is here.',
            'end_of_the_day_report_images' => $multipleFakeImages,
            'dtr_reason_of_late_entry' => 'The entry was late because of a late turn over.',
        ]);

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Failed to time out. You have an open break session.',
            ]);
    }

    public function testAuthenticatedUserFailsTimeOutIfLate(): void
    {
        // Arrange new user
        $user = User::factory()->withRoles(['employee', 'full_time', 'day_shift'])->create();
        Sanctum::actingAs($user);

        // Arrange DTR entry to time out 1 day late
        Dtr::factory()->withTimeIn()->create(['user_id' => $user->user_id, 'dtr_time_in' => Carbon::now()->addDay(1)]);

        // Arrange the time to sub one day for late entry
        $futureTime = Carbon::now()->subDay();
        Carbon::setTestNow($futureTime);

        // Arrange fake image for DTR time out
        $fakeImage = UploadedFile::fake()->image('dtr_time_out_image.jpg', 600, 600);
    
        // Arrange multiple fake images for the end of the day report
        $multipleFakeImages = [
            UploadedFile::fake()->image('image1.jpg', 600, 600),
            UploadedFile::fake()->image('image2.jpg', 600, 600),
            UploadedFile::fake()->image('image3.jpg', 600, 600),
            UploadedFile::fake()->image('image4.jpg', 600, 600),
        ];
    
        // Act the request to the updateTimeOut endpoint
        $response = $this->putJson(route('v1.dtrs.updateTimeOut'), [
            'dtr_time_out_image' => $fakeImage,
            'dtr_end_of_the_day_report' => 'The day has ended.',
            'end_of_the_day_report_images' => $multipleFakeImages,
            'dtr_reason_of_late_entry' => 'The entry was late because of a late turn over.',
        ]);
    
        // Assert that the response has the correct status and message
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Time-out is not a late entry.',
            ]);

        // Reset the time after the test
        Carbon::setTestNow();
    }
}
