<?php

namespace Tests\Feature\PostController;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_index_posts_successfully()
    {
        // Create 15 posts
        Post::factory()->count(15)->create();

        // Set pagination parameters
        $perPage = 10;
        $page = 1;

        // Call the index method
        $response = $this->getJson(route('posts.index', ['perPage' => $perPage, 'page' => $page]));

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Posts retrieved successfully.']);
        $response->assertJsonStructure([
            'message',
            'current_page',
            'data' => [
                '*' => ['post_id', 'post_title', 'post_content', 'created_at', 'updated_at'],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        // Assert the correct number of items are returned
        $this->assertCount($perPage, $response->json('data'));
    }

    public function test_index_posts_pagination()
    {
        // Create 25 posts
        Post::factory()->count(25)->create();

        // Set pagination parameters
        $perPage = 10;
        $page = 2;

        // Call the index method
        $response = $this->getJson(route('posts.index', ['perPage' => $perPage, 'page' => $page]));

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Posts retrieved successfully.']);
        $response->assertJson([
            'current_page' => $page,
            'from' => 11,
            'to' => 20,
            'per_page' => $perPage,
        ]);

        // Assert the correct number of items are returned
        $this->assertCount($perPage, $response->json('data'));
    }
}
