<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;
use Schoolees\Psgc\Support\PsgcCache;

class ClearPsgcCache extends Command
{
    protected $signature = 'psgc:cache-clear';

    protected $description = 'Invalidate cached PSGC lookups';

    public function handle(): int
    {
        if (! PsgcCache::enabled()) {
            $this->warn('PSGC caching is disabled (psgc.cache.enabled), so there is nothing to clear.');

            return self::SUCCESS;
        }

        PsgcCache::flush();

        $this->info('✅ PSGC cache invalidated.');

        return self::SUCCESS;
    }
}
