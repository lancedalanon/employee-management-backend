<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ProjectUser extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'project_users';

    protected $primaryKey = 'project_user_id';

    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'project_role',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when a project task status is saved
        static::saved(function () {
            ProjectUser::clearCache();
        });

        // Clear cache when a project task status is deleted
        static::deleted(function () {
            ProjectUser::clearCache();
        });
    }

    /**
     * Clear all cache keys related to posts.
     */
    public static function clearCache()
    {
        // Retrieve all post cache keys
        $cacheKeys = Cache::get('project_user_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('project_user_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param  string  $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('project_user_cache_keys', []);

        // Add the new key to the list if not already present
        if (! in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('project_user_cache_keys', $cacheKeys);
    }

    /**
     * Set the project role attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setProjectRoleAttribute($value)
    {
        $validRoles = config('constants.project_roles');

        if (! in_array($value, $validRoles)) {
            throw new \InvalidArgumentException("Invalid project role: $value");
        }

        $this->attributes['project_role'] = $value;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
