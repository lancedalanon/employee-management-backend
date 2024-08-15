<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ProjectTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'project_task_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_name',
        'project_task_description',
        'project_id',
        'project_task_progress',
        'project_task_priority_level',
        'user_id',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when a project task is saved
        static::saved(function () {
            ProjectTask::clearCache();
        });

        // Clear cache when a project task is deleted
        static::deleted(function () {
            ProjectTask::clearCache();
        });
    }

    /**
     * Clear all cache keys related to posts.
     */
    public static function clearCache()
    {
        // Retrieve all post cache keys
        $cacheKeys = Cache::get('project_task_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('project_task_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param  string  $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('project_task_cache_keys', []);

        // Add the new key to the list if not already present
        if (! in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('project_task_cache_keys', $cacheKeys);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function subtasks()
    {
        return $this->hasMany(ProjectTaskSubtask::class, 'project_task_id', 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
