<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait CacheManagement
{
    /**
     * Cache TTL configurations (in seconds)
     */
    protected const CACHE_TTL = [
        'short' => 300,      // 5 minutes - for frequently changing data
        'medium' => 900,     // 15 minutes - for moderately stable data
        'long' => 3600,      // 1 hour - for stable data
        'very_long' => 86400, // 24 hours - for very stable data
    ];
    
    /**
     * Cache tags for organized cache management
     * Note: Tags are only supported by Redis and Memcached cache drivers
     */
    protected const CACHE_TAGS = [
        'fundis' => 'fundis',
        'wards' => 'wards',
        'districts' => 'districts',
        'regions' => 'regions',
        'transactions' => 'transactions',
        'site_visits' => 'site_visits',
        'users' => 'users',
    ];

    /**
     * Generate a consistent cache key from prefix and parameters
     *
     * @param string $prefix
     * @param array $params
     * @return string
     */
    protected function generateCacheKey(string $prefix, array $params = []): string
    {
        // Remove null values
        $params = array_filter($params, fn($value) => $value !== null);
        
        // Sort for consistency
        ksort($params);
        
        // Sanitize string values to prevent cache key manipulation
        array_walk_recursive($params, function (&$value) {
            if (is_string($value)) {
                // Remove special characters that could cause issues
                $value = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $value);
                // Limit length to prevent excessively long keys
                $value = substr($value, 0, 100);
            }
        });
        
        // Create hash from parameters
        $hash = md5(json_encode($params));
        
        return "{$prefix}:{$hash}";
    }

    /**
     * Cache a value with tags support
     * Returns true on success, false on failure
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @param array $tags
     * @return bool
     */
    protected function cacheWithTags(string $key, $value, int $ttl, array $tags = []): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        try {
            if (!empty($tags) && $this->supportsTagging()) {
                return Cache::tags($tags)->put($key, $value, $ttl);
            }
            
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remember a value with tags support
     * This method caches the result of the callback if not already cached
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @param array $tags
     * @return mixed
     */
    protected function rememberWithTags(string $key, int $ttl, callable $callback, array $tags = [])
    {
        if (!$this->isCachingEnabled()) {
            return $callback();
        }

        try {
            if (!empty($tags) && $this->supportsTagging()) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }
            
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed, executing callback directly', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    /**
     * Get a value from cache with tags support
     *
     * @param string $key
     * @param array $tags
     * @return mixed
     */
    protected function getFromCache(string $key, array $tags = [])
    {
        if (!$this->isCachingEnabled()) {
            return null;
        }

        try {
            if (!empty($tags) && $this->supportsTagging()) {
                return Cache::tags($tags)->get($key);
            }
            
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Forget a cache key
     *
     * @param string $key
     * @return bool
     */
    protected function forgetCache(string $key): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Flush cache by tags
     * Only works with cache drivers that support tagging (Redis, Memcached)
     *
     * @param array $tags
     * @return bool
     */
    protected function flushCacheTags(array $tags): bool
    {
        if (empty($tags) || !$this->isCachingEnabled() || !$this->supportsTagging()) {
            return false;
        }
        
        try {
            return Cache::tags($tags)->flush();
        } catch (\Exception $e) {
            Log::warning('Cache tag flush failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all fundis-related caches
     *
     * @param string|null $uniqueId Optional specific fundi to clear
     * @return void
     */
    protected function clearFundiCaches(?string $uniqueId = null): void
    {
        // If we have a specific fundi, clear just that one's detail cache
        if ($uniqueId) {
            $cacheKey = $this->generateCacheKey('fundi.show', ['unique_id' => $uniqueId]);
            $this->forgetCache($cacheKey);
        }
        
        // Clear all fundis list caches using tags
        $this->flushCacheTags([self::CACHE_TAGS['fundis']]);
    }

    /**
     * Clear transaction-related caches
     *
     * @return void
     */
    protected function clearTransactionCaches(): void
    {
        $this->flushCacheTags([self::CACHE_TAGS['transactions']]);
        
        // Also clear fundi caches since they show transaction counts
        $this->flushCacheTags([self::CACHE_TAGS['fundis']]);
    }

    /**
     * Clear site visit-related caches
     *
     * @return void
     */
    protected function clearSiteVisitCaches(): void
    {
        $this->flushCacheTags([self::CACHE_TAGS['site_visits']]);
        
        // Also clear fundi caches since they show site visit relationships
        $this->flushCacheTags([self::CACHE_TAGS['fundis']]);
    }

    /**
     * Clear location-related caches
     *
     * @return void
     */
    protected function clearLocationCaches(): void
    {
        $this->flushCacheTags([
            self::CACHE_TAGS['wards'],
            self::CACHE_TAGS['districts'],
            self::CACHE_TAGS['regions'],
        ]);
    }

    /**
     * Clear all application caches
     * Use with caution - this is a nuclear option
     *
     * @return void
     */
    protected function clearAllCaches(): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        try {
            Cache::flush();
            Log::info('All application caches cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear all caches', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics (requires Redis)
     *
     * @return array|null
     */
    protected function getCacheStats(): ?array
    {
        if (!$this->isCachingEnabled() || config('cache.default') !== 'redis') {
            return null;
        }

        try {
            $redis = Cache::getRedis();
            $info = $redis->info('stats');
            
            return [
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate(
                    $info['keyspace_hits'] ?? 0,
                    $info['keyspace_misses'] ?? 0
                ),
                'total_keys' => $redis->dbSize(),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate cache hit rate percentage
     *
     * @param int $hits
     * @param int $misses
     * @return float
     */
    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($hits / $total) * 100, 2);
    }

    /**
     * Warm up cache with frequently accessed data
     * Should be called in a scheduled task (e.g., daily or hourly)
     *
     * @return void
     */
    protected function warmUpFundiCache(): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        try {
            // Cache active approved fundis
            $cacheKey = $this->generateCacheKey('fundis.active_approved', []);
            
            $this->rememberWithTags(
                $cacheKey,
                self::CACHE_TTL['long'],
                function () {
                    return \App\Models\Fundi::where('status', 'active')
                        ->whereNotNull('approved_at')
                        ->whereNotNull('approved_by')
                        ->with(['ward.district.region'])
                        ->get();
                },
                [self::CACHE_TAGS['fundis']]
            );

            Log::info('Fundi cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm up fundi cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    protected function isCachingEnabled(): bool
    {

        $driver = config('cache.default');
        return $driver !== null && 
               $driver !== 'null' && 
               $driver !== 'array';
    }

    /**
     * Check if current cache driver supports tagging
     * Only Redis and Memcached support cache tagging
     *
     * @return bool
     */
    protected function supportsTagging(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached'], true);
    }

    /**
     * Get appropriate cache TTL based on data type
     *
     * @param string $type (short|medium|long|very_long)
     * @return int
     */
    protected function getCacheTTL(string $type = 'medium'): int
    {
        return self::CACHE_TTL[$type] ?? self::CACHE_TTL['medium'];
    }

    /**
     * Get all cache tags defined
     *
     * @return array
     */
    protected function getCacheTags(): array
    {
        return self::CACHE_TAGS;
    }

    /**
     * Invalidate multiple cache patterns at once
     * Useful when an action affects multiple resources
     *
     * @param array $patterns Array of cache key patterns to invalidate
     * @return void
     */
    protected function invalidateCachePatterns(array $patterns): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        foreach ($patterns as $pattern) {
            $this->forgetCache($pattern);
        }
    }
}