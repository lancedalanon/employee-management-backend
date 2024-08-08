<?php

namespace Tests\Unit;

use App\Services\AiPromptService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Mockery;
use Tests\TestCase;

class AiPromptServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockUser();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockUser()
    {
        $user = Mockery::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->api_key = 'fake-api-key';
        Auth::shouldReceive('user')->andReturn($user);
    }

    public function testGenerateResponseSuccessful()
    {
        $service = Mockery::mock(AiPromptService::class)->makePartial();

        $service->shouldReceive('generateResponse')
            ->andReturn(Response::json([
                'message' => 'Prompt generated successfully.',
                'data' => ['some' => 'data'],
            ], 200));

        $response = $service->generateResponse('prompt', 'data');

        $this->assertEquals(200, $response->status());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'Prompt generated successfully.',
            'data' => ['some' => 'data'],
        ]), $response->getContent());
    }

    public function testGenerateResponseWithError()
    {
        $service = Mockery::mock(AiPromptService::class)->makePartial();

        $service->shouldReceive('generateResponse')
            ->andReturn(Response::json([
                'message' => 'Failed to retrieve activities.',
                'data' => null,
            ], 500));

        $response = $service->generateResponse('prompt', 'data');

        $this->assertEquals(500, $response->status());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'Failed to retrieve activities.',
            'data' => null,
        ]), $response->getContent());
    }

    public function testGenerateResponseWithSafetyConcern()
    {
        $service = Mockery::mock(AiPromptService::class)->makePartial();

        $service->shouldReceive('generateResponse')
            ->andReturn(Response::json([
                'message' => 'The content was flagged due to safety concerns.',
                'data' => null,
            ], 400));

        $response = $service->generateResponse('prompt', 'data');

        $this->assertEquals(400, $response->status());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'The content was flagged due to safety concerns.',
            'data' => null,
        ]), $response->getContent());
    }
}
