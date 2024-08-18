<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Retrieve data from cache or execute the callback and cache the result.
     *
     * @return mixed
     */
    public function remember(string $cacheKey, \Closure $callback, int $cacheDuration)
    {
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $data = $callback();
            Cache::put($cacheKey, $data, $cacheDuration);

            return $data;
        }
    }

    /**
     * Retrieve data from cache or execute the callback and cache the result indefinitely.
     *
     * @return mixed
     */
    public function rememberForever(string $cacheKey, \Closure $callback)
    {
        // Check if the cache already exists
        if (Cache::has($cacheKey)) {
            // Return the cached data
            return Cache::get($cacheKey);
        } else {
            // Execute the callback to get the data
            $data = $callback();
            // Store the data in the cache indefinitely
            Cache::forever($cacheKey, $data);

            // Return the data
            return $data;
        }
    }
}
