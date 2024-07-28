<?php

namespace Tests\Feature\PostController;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeletePostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);
        Sanctum::actingAs($user);
    }

    public function test_destroy_post_successfully()
    {
        // Create a post with tags
        $post = Post::factory()->create();
        $post->tags()->createMany([
            ['post_tag' => 'tag1'],
            ['post_tag' => 'tag2']
        ]);

        // Call the destroy method
        $response = $this->deleteJson(route('admin.posts.destroy', $post->post_id));

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Post deleted successfully.']);
        $this->assertSoftDeleted('posts', ['post_id' => $post->post_id]);
        $this->assertSoftDeleted('post_tags', ['post_id' => $post->post_id]);
    }

    public function test_destroy_non_existing_post()
    {
        $response = $this->deleteJson(route('admin.posts.destroy', 9999));

        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'Post not found.']);
    }
}
