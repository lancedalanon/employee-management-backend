<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class StoreTimeOutTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $dtr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full-time']);
        Role::create(['name' => 'day-shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles()->create();
        Sanctum::actingAs($this->user);

        // Create a sample DTR record for the user with a time-in event
        $this->dtr = Dtr::factory()->create(['user_id' => $this->user->user_id]);

        // Set up fake storage disk
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full-time', 'day-shift'])->delete();
        $this->user = null;
        $this->dtr = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanTimeOut(): void
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
    
        // Act the request to the storeTimeOut endpoint
        $response = $this->postJson(route('v1.dtrs.storeTimeOut'), [
            'dtr_time_out_image' => $fakeImage,
            'dtr_end_of_the_day_report' => 'The day has ended.',
            'end_of_the_day_report_images' => $multipleFakeImages,
        ]);
    
        // Assert that the response has the correct status and message
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Timed out successfully.',
            ]);
    
        // Assert that the DTR record in the database is updated correctly
        $this->assertDatabaseHas('dtrs', [
            'user_id' => $this->user->user_id,
            'dtr_time_out_image' => 'dtr_time_out_images/' . $fakeImage->hashName(),
            'dtr_end_of_the_day_report' => 'The day has ended.',
        ]);
    
        // Assert that the end of the day report images are stored correctly
        foreach ($multipleFakeImages as $file) {
            $this->assertDatabaseHas('end_of_the_day_report_images', [
                'dtr_id' => $this->dtr->dtr_id,
                'end_of_the_day_report_image' => 'end_of_the_day_report_images/' . $file->hashName(),
            ]);
        }
    
        // Assert that the files are stored correctly in the storage
        Storage::disk('public')->assertExists('dtr_time_out_images/' . $fakeImage->hashName());
    
        foreach ($multipleFakeImages as $file) {
            Storage::disk('public')->assertExists('end_of_the_day_report_images/' . $file->hashName());
        }
    }    
}
