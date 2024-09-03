<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreBreakTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $dtr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'full_time']);
        Role::create(['name' => 'day_shift']);

        // Create a sample user and assign the roles
        $this->user = User::factory()->withRoles()->create();
        Sanctum::actingAs($this->user);

        // Create a sample DTR record for the user with a time-in event
        $this->dtr = Dtr::factory()->withTimeIn()->create(['user_id' => $this->user->user_id]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full_time', 'day_shift'])->delete();
        $this->user = null;
        $this->dtr = null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanAddBreak(): void
    {
        // Act the request to the storeBreak endpoint
        $response = $this->postJson(route('v1.dtrs.storeBreak'));

        // Assert that the response has the correct status and message
        $response->assertStatus(201)
        ->assertJson([
            'message' => 'Break time was added successfully.',
        ]);
        
        // Assert that the DTR record in the database is updated correctly
        $this->assertDatabaseHas('dtr_breaks', [
            'dtr_id' => $this->dtr->dtr_id,
            'dtr_break_break_time' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function testAuthenticatedUserFailsToAddBreakIfNoTimeInIsSet(): void
    {
        // Arrange a new user
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);

        // Act the request to the storeBreak endpoint
        $response = $this->postJson(route('v1.dtrs.storeBreak'));

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Failed to add break time. You have not timed in yet.',
            ]);
    }

    public function testAuthenticatedUserFailsToAddBreakIfThereIsOpenBreak(): void
    {
        // Arrange Dtr to have an existing break
        DtrBreak::factory()->onlyBreakTime()->create(['dtr_id' => $this->dtr->dtr_id]);

        // Act the request to the storeBreak endpoint
        $response = $this->postJson(route('v1.dtrs.storeBreak'));

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
        ->assertJson([
            'message' => 'Failed to add break time. You have an open break time session.',
        ]);
    }
}
