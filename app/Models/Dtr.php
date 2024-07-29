<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Dtr extends Model
{
    use HasFactory;

    protected $primaryKey = 'dtr_id';

    protected $fillable = [
        'user_id',
        'time_in',
        'time_out',
        'end_of_the_day_report',
        'is_overtime',
        'is_absent'
    ];

    protected $dates = [
        'time_in',
        'time_out'
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when a dtr is saved
        static::saved(function ($dtr) {
            Dtr::clearCache();
        });

        // Clear cache when a dtr is deleted
        static::deleted(function ($dtr) {
            Dtr::clearCache();
        });
    }

    /**
     * Clear all cache keys related to dtrs.
     */
    public static function clearCache()
    {
        // Retrieve all dtr cache keys
        $cacheKeys = Cache::get('dtr_cache_keys', []);

        // Clear each cache key
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // Clear the list of cache keys
        Cache::forget('dtr_cache_keys');
    }

    /**
     * Remember a cache key.
     *
     * @param string $key
     */
    public static function rememberCacheKey($key)
    {
        // Retrieve current cache keys
        $cacheKeys = Cache::get('dtr_cache_keys', []);

        // Add the new key to the list if not already present
        if (!in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
        }

        // Store the updated list of cache keys
        Cache::put('dtr_cache_keys', $cacheKeys);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function breaks()
    {
        return $this->hasMany(DtrBreak::class, 'dtr_id', 'dtr_id');
    }

    public function endOfTheDayReportImages()
    {
        return $this->hasMany(EndOfTheDayReportImage::class, 'dtr_id', 'dtr_id');
    }
}
