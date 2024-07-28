<?php

namespace Tests\Feature\PostController;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePostTest extends TestCase
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

    public function test_can_create_a_post_with_tags_and_media_files()
    {
        // Prepare the media files
        $mediaFiles = [
            UploadedFile::fake()->image('media1.jpg'),
            UploadedFile::fake()->image('media2.jpg'),
        ];

        // Prepare the tags
        $tags = [
            'tag1',
            'tag2',
        ];

        // Call the store method
        $response = $this->postJson(route('admin.posts.store'), [
            'post_title' => 'Test Post Title',
            'post_content' => 'Test post content with media1.jpg and media2.jpg',
            'post_tags' => $tags,
            'post_media' => $mediaFiles,
        ]);

        // Assert the response
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Post created successfully.',
        ]);

        // Assert the post is created
        $post = Post::where('post_title', 'Test Post Title')->first();
        $this->assertNotNull($post);

        // Assert the post content contains the URLs of the uploaded media files
        foreach ($mediaFiles as $mediaFile) {
            $url = Storage::disk('public')->url('post_media_files/' . $mediaFile->hashName());
            $this->assertStringContainsString($url, $post->post_content);
        }

        $this->assertEquals(Auth::id(), $post->user_id);

        // Assert the tags are created
        $this->assertCount(2, $post->tags);
        $this->assertTrue($post->tags->pluck('post_tag')->contains('tag1'));
        $this->assertTrue($post->tags->pluck('post_tag')->contains('tag2'));

        // Assert the media files are created
        $this->assertCount(2, $post->media);
        foreach ($mediaFiles as $mediaFile) {
            Storage::disk('public')->assertExists('post_media_files/' . $mediaFile->hashName());
            $this->assertTrue($post->media->pluck('post_media')->contains('post_media_files/' . $mediaFile->hashName()));
        }
    }

    public function test_post_creation_fails_without_post_title()
    {
        $response = $this->postJson(route('admin.posts.store'), [
            'post_content' => 'Test post content',
            'post_tags' => ['tag1', 'tag2'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_title');
    }

    public function test_post_creation_fails_without_post_content()
    {
        $response = $this->postJson(route('admin.posts.store'), [
            'post_title' => 'Test Post Title',
            'post_tags' => ['tag1', 'tag2'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_content');
    }

    public function test_post_creation_fails_without_post_tags()
    {
        $response = $this->postJson(route('admin.posts.store'), [
            'post_title' => 'Test Post Title',
            'post_content' => 'Test post content',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_tags');
    }

    public function test_post_creation_fails_with_empty_post_tags()
    {
        $response = $this->postJson(route('admin.posts.store'), [
            'post_title' => 'Test Post Title',
            'post_content' => 'Test post content',
            'post_tags' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_tags');
    }

    public function test_post_creation_fails_with_invalid_media_files()
    {
        $mediaFiles = [
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        $response = $this->postJson(route('admin.posts.store'), [
            'post_title' => 'Test Post Title',
            'post_content' => 'Test post content',
            'post_tags' => ['tag1', 'tag2'],
            'post_media' => $mediaFiles,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('post_media.0');
    }
}
