<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\User;
use App\Testing\DtrTestingTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResumeControllerTest extends TestCase
{
    use DtrTestingTrait, RefreshDatabase;

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
     * Test the resume method for resuming a break.
     *
     * @return void
     */
    public function test_resume_break()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::now()->subMinutes(30);
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        // Create a DtrBreak entry for the user
        $breakTime = Carbon::now();
        DtrBreak::factory()->withBreakTime($breakTime)->create([
            'dtr_id' => $dtr->dtr_id,
        ]);

        $response = $this->postJson(route('dtrs.storeResume', [
            'dtrId' => $dtr->dtr_id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Break resumed successfully.',
        ]);

        // Verify the response contains the updated DtrBreak entry
        $response->assertJsonStructure([
            'message',
            'data' => [
                'dtr_break_id',
                'dtr_id',
                'break_time',
                'resume_time',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /**
     * Test the resume method when DTR record is not found.
     *
     * @return void
     */
    public function test_resume_break_dtr_not_found()
    {
        $response = $this->postJson(route('dtrs.storeResume', [
            'dtrId' => 99999,
        ]));

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'DTR record not found.',
        ]);
    }

    /**
     * Test the resume method when no open break session is found.
     *
     * @return void
     */
    public function test_resume_break_no_open_session()
    {
        // Add a new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr
        $timeIn = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $response = $this->postJson(route('dtrs.storeResume', [
            'dtrId' => $dtr->dtr_id,
        ]));

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Failed to resume break. No open break session found.',
        ]);
    }
}
