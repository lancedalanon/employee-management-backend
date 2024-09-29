<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Company;
use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTimeInTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $companyAdmin;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Set the test time to 08:00:00
        $now = Carbon::now()->setHour(8)->setMinute(0)->setSecond(0);
        Carbon::setTestNow($now);

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

        Sanctum::actingAs($this->user);

        // Set up fake storage disk
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        Carbon::setTestNow();
        
        parent::tearDown();
    }

    public function testAuthenticatedUserCanTimeIn(): void
    {
        // Arrange
        $fakeImage = UploadedFile::fake()->image('dtr_time_in_image.jpg', 600, 600);

        // Act
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => $fakeImage
        ]);

        // Assert the response status and message
        $response->assertStatus(201)
                    ->assertJson([
                        'message' => 'Timed in successfully.',
                    ]);

        // Assert that the Dtr record was created in the database
        $this->assertDatabaseHas('dtrs', [
            'user_id' => $this->user->user_id,
            'dtr_time_in' => Carbon::now()->toDateTimeString(), // Adjust for exact match if necessary
        ]);
    }

    public function testAuthenticatedUserFailsTimeInWithMissingField(): void
    {
        // Act
        $response = $this->postJson(route('v1.dtrs.storeTimeIn'), [
            'dtr_time_in_image' => ''
        ]);

        // Assert the response status and message
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['dtr_time_in_image']);
    }
    
    public function testAuthenticatedUserFailsTimeInWithInvalidField(): void
    {
        // Arrange
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
        // Arrange
        $fakeImage = UploadedFile::fake()->image('dtr_time_in_image.jpg', 600, 600);
        Dtr::factory()->withTimeIn()->create(['user_id' => $this->user->user_id, 'dtr_time_in' => Carbon::now()]);

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
