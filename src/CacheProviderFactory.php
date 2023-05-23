<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\HttpGuard;

use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;

class CacheProviderFactory
{
    /**
     * @return AuthenticatableCacheProvider
     */
    public function make(string $driver = 'array')
    {
        switch (strtolower($driver)) {
            case 'array':
                return ArrayCacheProvider::load();
            case 'redis':
                return new RedisCacheProvider();
            case 'memcached':
                $parameters = HttpGuardGlobals::forMemcached();
                $memcached = (new MemcachedConnector())->connect(
                    $parameters['servers'] ?? [],
                    $parameters['persistent_id'] ?? null,
                    $parameters['options'] ?? [],
                    $parameters['sasl'] ?? []
                );

                return new MemcachedCacheProvider($memcached);
            default:
                return ArrayCacheProvider::load();
        }
    }
}
