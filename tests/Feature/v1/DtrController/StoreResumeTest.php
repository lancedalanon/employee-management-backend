<?php

namespace Tests\Feature\v1\DtrController;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreResumeTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $dtr;
    protected $dtrBreak;

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

        // Create a sample break record for the DTR record
        $this->dtrBreak = DtrBreak::factory()->onlyBreakTime()->create(['dtr_id' => $this->dtr->dtr_id]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['employee', 'full-time', 'day-shift'])->delete();
        $this->user = null;
        $this->dtr = null;
        $this->dtrBreak= null;

        parent::tearDown();
    }

    public function testAuthenticatedUserCanAddResumeTime(): void
    {
        // Act the request to the storeResume endpoint
        $response = $this->postJson(route('v1.dtrs.storeResume'));

        // Assert that the response has the correct status and message
        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Resume time was added successfully.',
                ]);
                
        // Assert that the DTR record in the database is updated correctly
        $this->assertDatabaseHas('dtr_breaks', [
            'dtr_id' => $this->dtr->dtr_id,
            'dtr_break_break_time' => $this->dtr->breaks()->first()->dtr_break_break_time,
            'dtr_break_resume_time' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function testAuthenticatedUserFailsToAddResumeTimeIfTimeInIsNotSet(): void
    {
        // Arrange a sample user and assign the roles
        $user = User::factory()->withRoles()->create();
        Sanctum::actingAs($user);

        // Act the request to the storeResume endpoint
        $response = $this->postJson(route('v1.dtrs.storeResume'));

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Failed to add resume time. You have not timed in yet.',
                ]);
    }

    public function testAuthenticatedUserFailsToAddResumeTimeIfThereIsNoOpenBreak(): void
    {
        // Arrange dtr break without an open break
        DtrBreak::where('dtr_break_id', $this->dtrBreak->dtr_break_id)->delete();

        // Act the request to the storeResume endpoint
        $response = $this->postJson(route('v1.dtrs.storeResume'));

        // Assert that the response has the correct status and message
        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Failed to add resume time. There is no open break session.',
                ]);
    }
}
