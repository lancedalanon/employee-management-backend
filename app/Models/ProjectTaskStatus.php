<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ProjectTaskStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_status_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_id',
        'project_task_status',
        'project_task_status_media_file',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when a project task status is saved
        static::saved(function () {
            ProjectTaskStatus::clearCache();
        });

        // Clear cache when a project task status is deleted
        static::deleted(function () {
            ProjectTaskStatus::clearCache();
        });
    }

    /**
     * Clear all cache keys related to posts.
     */
    public static function clearCache()
    {
        // Retrieve all post cache keys
        $cacheKeys = Cache::get('project_task_status_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('project_task_status_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param string $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('project_task_status_cache_keys', []);

        // Add the new key to the list if not already present
        if (!in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('project_task_status_cache_keys', $cacheKeys);
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id', 'project_task_id');
    }
}
