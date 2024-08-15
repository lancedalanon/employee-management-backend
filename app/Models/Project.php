<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_id';

    public $timestamps = true;

    protected $fillable = [
        'project_name',
        'project_description',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when a project is saved
        static::saved(function () {
            Dtr::clearCache();
        });

        // Clear cache when a project is deleted
        static::deleted(function () {
            Dtr::clearCache();
        });
    }

    /**
     * Clear all cache keys related to dtrs.
     */
    public static function clearCache()
    {
        // Retrieve all dtr cache keys
        $cacheKeys = Cache::get('project_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('project_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param  string  $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('project_cache_keys', []);

        // Add the new key to the list if not already present
        if (! in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('project_cache_keys', $cacheKeys);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id')
            ->withPivot('project_role')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id', 'project_id');
    }
}
