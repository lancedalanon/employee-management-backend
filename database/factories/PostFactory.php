<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_title' => $this->faker->sentence,
            'post_content' => $this->faker->paragraph,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the post should have tags.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withTags(array $tags = [])
    {
        return $this->afterCreating(function (Post $post) use ($tags) {
            foreach ($tags as $tag) {
                $post->tags()->create(['post_tag' => $tag]);
            }
        });
    }

    /**
     * Indicate that the post should have media files.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withMedia(array $mediaFiles = [])
    {
        return $this->afterCreating(function (Post $post) use ($mediaFiles) {
            foreach ($mediaFiles as $media) {
                $post->media()->create([
                    'post_media' => $media['path'],
                    'post_media_type' => $media['mime_type'],
                ]);
            }
        });
    }
}
