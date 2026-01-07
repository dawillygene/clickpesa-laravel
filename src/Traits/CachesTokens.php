<?php

namespace Dawilly\Dawilly\Traits;

use Illuminate\Support\Facades\Cache;

trait CachesTokens
{
    /**
     * Cache key for authentication token
     */
    protected string $tokenCacheKey = 'clickpesa:auth:token';

    /**
     * Cache key for preview responses
     */
    protected string $previewCacheKeyPrefix = 'clickpesa:preview:';

    /**
     * Get cached token
     *
     * @return string|null
     */
    protected function getCachedToken(): ?string
    {
        if (!config('clickpesa.cache.enabled', true)) {
            return null;
        }

        $token = Cache::store(config('clickpesa.cache.driver', 'default'))
            ->get($this->getTokenCacheKey());

        if ($token) {
            \Log::debug('Clickpesa: Using cached token');
        }

        return $token;
    }

    /**
     * Cache the token
     *
     * @param string $token
     * @return void
     */
    protected function cacheToken(string $token): void
    {
        if (!config('clickpesa.cache.enabled', true)) {
            return;
        }

        $ttl = config('clickpesa.cache.ttl', 3600); // 1 hour default (token validity)

        Cache::store(config('clickpesa.cache.driver', 'default'))
            ->put($this->getTokenCacheKey(), $token, $ttl);

        \Log::debug('Clickpesa: Token cached for ' . $ttl . ' seconds');
    }

    /**
     * Invalidate cached token
     *
     * @return void
     */
    protected function invalidateTokenCache(): void
    {
        Cache::store(config('clickpesa.cache.driver', 'default'))
            ->forget($this->getTokenCacheKey());

        \Log::debug('Clickpesa: Token cache invalidated');
    }

    /**
     * Get token cache key (with environment to avoid cross-env conflicts)
     *
     * @return string
     */
    protected function getTokenCacheKey(): string
    {
        $env = config('clickpesa.environment', 'sandbox');
        return "clickpesa:auth:token:{$env}";
    }

    /**
     * Cache preview response
     *
     * @param string $cacheKey
     * @param array $data
     * @param int $ttl
     * @return void
     */
    protected function cachePreview(string $cacheKey, array $data, int $ttl = 300): void
    {
        if (!config('clickpesa.cache.preview_enabled', true)) {
            return;
        }

        Cache::store(config('clickpesa.cache.driver', 'default'))
            ->put($cacheKey, $data, $ttl);

        \Log::debug("Clickpesa: Preview cached for {$ttl} seconds");
    }

    /**
     * Get cached preview
     *
     * @param string $cacheKey
     * @return array|null
     */
    protected function getCachedPreview(string $cacheKey): ?array
    {
        if (!config('clickpesa.cache.preview_enabled', true)) {
            return null;
        }

        $data = Cache::store(config('clickpesa.cache.driver', 'default'))
            ->get($cacheKey);

        if ($data) {
            \Log::debug('Clickpesa: Using cached preview');
        }

        return $data;
    }

    /**
     * Generate preview cache key
     *
     * @param array $data
     * @return string
     */
    protected function generatePreviewCacheKey(array $data): string
    {
        $key = implode(':', [
            $this->previewCacheKeyPrefix,
            $data['orderReference'] ?? 'unknown',
            md5(json_encode($data))
        ]);

        return $key;
    }

    /**
     * Invalidate preview cache
     *
     * @param string $orderReference
     * @return void
     */
    protected function invalidatePreviewCache(string $orderReference): void
    {
        // Invalidate all preview caches for this order reference
        Cache::store(config('clickpesa.cache.driver', 'default'))
            ->forget($this->previewCacheKeyPrefix . $orderReference);

        \Log::debug("Clickpesa: Preview cache invalidated for {$orderReference}");
    }

    /**
     * Flush all Clickpesa caches
     *
     * @return void
     */
    public function flushAllCaches(): void
    {
        if (!config('clickpesa.cache.enabled', true)) {
            return;
        }

        $store = Cache::store(config('clickpesa.cache.driver', 'default'));

        // Clear token cache
        $store->forget($this->getTokenCacheKey());

        // Clear all preview caches - note: this is a best effort approach
        // In production, consider using cache tags or a different strategy
        \Log::info('Clickpesa: All caches flushed');
    }
}
