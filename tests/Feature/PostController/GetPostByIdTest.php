<?php

namespace Tests\Feature\PostController;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetPostByIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_show_post_successfully()
    {
        // Create a post with tags
        $post = Post::factory()->create();
        $post->tags()->createMany([
            ['post_tag' => 'tag1'],
            ['post_tag' => 'tag2'],
        ]);

        // Call the show method
        $response = $this->getJson(route('posts.show', $post->post_id));

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Post entry retrieved successfully.']);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'post_id',
                'post_title',
                'post_content',
                'created_at',
                'updated_at',
                'tags' => [
                    '*' => ['post_tag'],
                ],
            ],
        ]);
    }

    public function test_show_non_existing_post()
    {
        // Call the show method with a non-existing post ID
        $response = $this->getJson(route('posts.show', 9999));

        // Assert the response
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'Post not found.']);
    }
}
