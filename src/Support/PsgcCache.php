<?php

namespace Schoolees\Psgc\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Opt-in caching for PSGC lookups.
 *
 * PSGC data only changes when the dataset is re-seeded, so results are safe to
 * cache for long periods. Invalidation is by version counter rather than cache
 * tags, since tags require a taggable store (redis/memcached) and this has to
 * work on the file and database drivers too.
 */
final class PsgcCache
{
    private const VERSION_KEY = 'psgc:version';

    public static function enabled(): bool
    {
        return (bool) config('psgc.cache.enabled', false);
    }

    /**
     * Run $callback, caching its result when caching is enabled.
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        if (! self::enabled()) {
            return $callback();
        }

        $ttl = (int) config('psgc.cache.ttl', 86400);

        return self::store()->remember(self::qualify($key), $ttl, $callback);
    }

    /**
     * Invalidate everything by moving the version the keys are built from.
     *
     * Old entries are left to expire on their own rather than being walked and
     * deleted, which no store can do efficiently without tags.
     */
    public static function flush(): void
    {
        $store = self::store();
        $store->forever(self::VERSION_KEY, (int) $store->get(self::VERSION_KEY, 0) + 1);
    }

    public static function version(): int
    {
        return self::enabled() ? (int) self::store()->get(self::VERSION_KEY, 0) : 0;
    }

    /**
     * Build a stable key from the query shape.
     */
    public static function key(string $model, array $parts): string
    {
        ksort($parts);

        return $model . ':' . md5(json_encode($parts));
    }

    private static function qualify(string $key): string
    {
        return 'psgc:' . self::version() . ':' . $key;
    }

    private static function store()
    {
        $store = config('psgc.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }
}
