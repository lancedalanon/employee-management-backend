<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_title',
        'post_content',
        'user_id',
        'published_at',
        'is_draft',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate slug when creating a post
        static::creating(function ($post) {
            $post->post_slug = Str::slug($post->post_title);
        });

        // Automatically update slug when updating a post
        static::updating(function ($post) {
            $post->post_slug = Str::slug($post->post_title);
        });

        // Clear cache when a post is saved
        static::saved(function () {
            Post::clearCache();
        });

        // Clear cache when a post is deleted
        static::deleted(function () {
            Post::clearCache();
        });
    }

    /**
     * Clear all cache keys related to posts.
     */
    public static function clearCache()
    {
        // Retrieve all post cache keys
        $cacheKeys = Cache::get('post_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('post_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param string $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('post_cache_keys', []);

        // Add the new key to the list if not already present
        if (!in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('post_cache_keys', $cacheKeys);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function tags()
    {
        return $this->hasMany(PostTag::class, 'post_id', 'post_id');
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class, 'post_id', 'post_id');
    }
}
