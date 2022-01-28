<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\ArrayCacheProvider;
use Drewlabs\AuthHttpGuard\RedisCacheProvider;
use Drewlabs\AuthHttpGuard\Contracts\AuthenticatableCacheProvider;

class CacheProviderFactory
{
    /**
     * 
     * @param string $driver 
     * @return AuthenticatableCacheProvider
     */
    public function make(string $driver = 'array')
    {
        switch (strtolower($driver)) {
            case 'array':
                return ArrayCacheProvider::load();
            case 'redis':
                return new RedisCacheProvider;
            default:
                return ArrayCacheProvider::load();
        }
    }
}
