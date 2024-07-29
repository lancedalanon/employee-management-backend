<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    protected $cacheDuration;

    public function __construct()
    {
        $this->cacheDuration = 60;
    }

    /**
     * Retrieve data from cache or execute the callback and cache the result.
     *
     * @param string $cacheKey
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $cacheKey, \Closure $callback)
    {
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $data = $callback();
            Cache::put($cacheKey, $data, $this->cacheDuration);
            return $data;
        }
    }

    /**
     * Retrieve data from cache or execute the callback and cache the result indefinitely.
     *
     * @param string $cacheKey
     * @param \Closure $callback
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
