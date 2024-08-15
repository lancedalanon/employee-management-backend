<?php

namespace Tests\Feature\PostController;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup method to create user, admin, and projects.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);
        Sanctum::actingAs($user);
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_update_post_successfully()
    {
        // Create a post with tags and media
        $post = Post::factory()->create();
        $post->tags()->createMany([
            ['post_tag' => 'tag1'],
            ['post_tag' => 'tag2'],
        ]);
        $media_files = [
            ['post_media' => 'path/to/media1.jpg', 'post_media_type' => 'image/jpeg'],
            ['post_media' => 'path/to/media2.jpg', 'post_media_type' => 'image/jpeg'],
        ];
        $post->media()->createMany($media_files);

        // Prepare new data
        $new_data = [
            'post_title' => 'Updated Title',
            'post_content' => 'Updated content.',
            'post_tags' => ['tag3', 'tag4'],
        ];

        // Prepare new media files
        $new_media_files = [
            UploadedFile::fake()->image('new_media1.jpg'),
            UploadedFile::fake()->image('new_media2.jpg'),
        ];

        // Call the update method
        $response = $this->putJson(route('admin.posts.update', $post->post_id), array_merge(
            $new_data,
            ['post_media' => $new_media_files]
        ));

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Post updated successfully.']);
        $response->assertJsonFragment(['post_title' => $new_data['post_title']]);
        $response->assertJsonFragment(['post_content' => $new_data['post_content']]);
        $response->assertJsonFragment(['post_tag' => 'tag3']);
        $response->assertJsonFragment(['post_tag' => 'tag4']);

        // Assert the database has the updated data
        $this->assertDatabaseHas('posts', [
            'post_id' => $post->post_id,
            'post_title' => $new_data['post_title'],
            'post_content' => $new_data['post_content'],
        ]);
        $this->assertDatabaseHas('post_tags', [
            'post_id' => $post->post_id,
            'post_tag' => 'tag3',
        ]);
        $this->assertDatabaseHas('post_tags', [
            'post_id' => $post->post_id,
            'post_tag' => 'tag4',
        ]);

        // Assert the old media files are deleted and new ones are added
        Storage::disk('public')->assertMissing('path/to/media1.jpg');
        Storage::disk('public')->assertMissing('path/to/media2.jpg');
        Storage::disk('public')->assertExists('post_media_files/'.$new_media_files[0]->hashName());
        Storage::disk('public')->assertExists('post_media_files/'.$new_media_files[1]->hashName());
    }

    public function test_update_non_existing_post()
    {
        $response = $this->putJson(route('admin.posts.update', 9999), [
            'post_title' => 'Non Existing Title',
            'post_content' => 'Non Existing content.',
            'post_tags' => ['tag1', 'tag2'],
        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'Post not found.']);
    }

    public function test_update_post_validation_errors_missing_post_title()
    {
        // Create a post
        $post = Post::factory()->create();

        // Missing post_title
        $response = $this->putJson(route('admin.posts.update', $post->post_id), [
            'post_content' => 'Updated content.',
            'post_tags' => ['tag1', 'tag2'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_title');
    }

    public function test_update_post_validation_errors_missing_post_content()
    {
        // Create a post
        $post = Post::factory()->create();

        // Missing post_content
        $response = $this->putJson(route('admin.posts.update', $post->post_id), [
            'post_title' => 'Updated Title',
            'post_tags' => ['tag1', 'tag2'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_content');
    }

    public function test_update_post_validation_errors_invalid_post_tags()
    {
        // Create a post
        $post = Post::factory()->create();

        // Invalid post_tags
        $response = $this->putJson(route('admin.posts.update', $post->post_id), [
            'post_title' => 'Updated Title',
            'post_content' => 'Updated content.',
            'post_tags' => 'invalid_tag', // Should be an array
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_tags');
    }

    public function test_update_post_validation_errors_invalid_post_media_type()
    {
        // Create a post
        $post = Post::factory()->create();

        // Invalid post_media_type
        $invalidMediaFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $response = $this->putJson(route('admin.posts.update', $post->post_id), [
            'post_title' => 'Updated Title',
            'post_content' => 'Updated content.',
            'post_tags' => ['tag1', 'tag2'],
            'post_media' => [$invalidMediaFile],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_media.0');
    }
}
