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

class BreakControllerTest extends TestCase
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
     * Test the break method for starting a break.
     *
     * @return void
     */
    public function test_start_break()
    {
        // Add new user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Specific timestamps for Dtr and DtrBreak
        $timeIn = Carbon::now();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $response = $this->postJson(route('dtrs.storeBreak', [
            'dtrId' => $dtr->dtr_id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Break started successfully.',
        ]);

        // Verify the response contains the latest DtrBreak entry
        $response->assertJsonStructure([
            'message',
            'data' => [
                'dtr_break_id',
                'dtr_id',
                'break_time',
                'created_at',
                'updated_at',
            ],
        ]);

        // Check if the break was recorded in the database
        $this->assertDatabaseHas('dtr_breaks', [
            'dtr_id' => $dtr->dtr_id,
        ]);
    }

    /**
     * Test the break method when there is an open break session.
     *
     * @return void
     */
    public function test_start_break_with_open_break_session()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $timeIn = Carbon::now()->subHour();
        $dtr = Dtr::factory()->withTimeIn($timeIn)->create([
            'user_id' => $user->user_id,
        ]);

        $break = Carbon::now()->subMinutes(30);
        DtrBreak::factory()->withBreakTime($break)->create([
            'dtr_id' => $dtr->dtr_id,
        ]);

        $response = $this->postJson(route('dtrs.storeBreak', [
            'dtrId' => $dtr->dtr_id,
        ]));

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Failed to start break. You have an open break session.',
        ]);
    }

    /**
     * Test the break method when DTR record is not found.
     *
     * @return void
     */
    public function test_start_break_dtr_not_found()
    {
        $response = $this->postJson(route('dtrs.storeBreak', [
            'dtrId' => 99999,
        ]));

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'DTR record not found.',
        ]);
    }
}
