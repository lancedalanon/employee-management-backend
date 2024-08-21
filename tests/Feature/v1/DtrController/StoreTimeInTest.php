<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTimeInTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;

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

        // Set up fake storage disk
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full-time', 'day-shift'])->delete();
        $this->user = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanTimeIn(): void
    {
        // Arrange fake image
        $fakeImage = UploadedFile::fake()->image('dtr_time_in_image.jpg', 600, 600);

        // Act the response
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => $fakeImage
        ]);

        // Assert the response status and message
        $response->assertStatus(201)
                    ->assertJson([
                        'message' => 'Timed in successfully.',
                    ]);

       // Assert that the image was stored
       Storage::disk('public')->assertExists('dtr_time_in_images/' . $fakeImage->hashName());

       // Assert that the Dtr record was created in the database
       $this->assertDatabaseHas('dtrs', [
           'user_id' => $this->user->user_id,
           'dtr_time_in' => Carbon::now()->toDateTimeString(), // Adjust for exact match if necessary
           'dtr_time_in_image' => 'dtr_time_in_images/' . $fakeImage->hashName(),
       ]);
    }

    public function testAuthenticatedUserFailsTimeInWithMissingField(): void
    {
        // Act the response
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => ''
        ]);

        // Assert the response status and message
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['dtr_time_in_image']);
    }
    
    public function testAuthenticatedUserFailsTimeInWithInvalidField(): void
    {
        // Arrange an invalid file type
        $invalidFile = UploadedFile::fake()->create('invalid_file.txt', 100, 'text/plain');

        // Act: Send the request with the invalid file
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => $invalidFile
        ]);

        // Assert: Check the response status and validation errors
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dtr_time_in_image']);
    }

    public function testAuthenticatedUserFailsToTimeInIfAlreadyTimedIn(): void
    {
        // Arrange fake image
        $fakeImage = UploadedFile::fake()->image('dtr_time_in_image.jpg', 600, 600);

        // Act the response
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => $fakeImage
        ]);

        // Act the response again
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => $fakeImage
        ]);

        // Assert the response status and message
        $response->assertStatus(400)
                    ->assertJson([
                        'message' => 'Time in failed. You currently have an open time in session.',
                    ]);
    }
}
