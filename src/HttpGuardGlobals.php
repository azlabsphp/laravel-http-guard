<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\HttpGuard;


/** @deprecated use `Globals` helper instead */
class HttpGuardGlobals
{

    /**
     * @param array<int,array<string,string|bool>> $servers
     *
     * @return array<int,array<string,string|bool>>
     */
    public static function hosts($servers = [])
    {
        return Globals::hosts($servers);
    }

    /**
     * @return string
     */
    public static function userPath(?string $path = null)
    {
        return Globals::userPath($path);
    }

    /**
     * @return string
     */
    public static function revokePath(?string $path = null)
    {
        return Globals::revokeTokenPath($path);
    }

    /**
     * @return bool
     */
    public static function usesCache(?bool $value = null)
    {
        return Globals::usesCache($value);
    }

    public static function nodeServerCachePath(?string $path = null)
    {
        return Globals::nodesCachePath($path);
    }

    /**
     * @param array|string $options
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function forRedis($options = null)
    {
        return Globals::forRedis($options);
    }

    /**
     * @return (string|int)[]
     */
    public static function redis()
    {
        return Globals::redis();
    }

    /**
     * @param string|null $guard
     *
     * @return string
     */
    public static function defaultGuards($guard = null)
    {
        return Globals::defaultGuards($guard);
    }

    public static function authenticatable(?string $name = null)
    {
        return Globals::authenticatable($name);
    }

    public static function useCacheDriver(string $driver)
    {
        return Globals::useCacheDriver($driver);
    }

    public static function defaultCacheDriver()
    {
        return Globals::getCacheDriver();
    }

    public static function forMemcached(?array $config = null)
    {
        return Globals::forMemcached($config);
    }

    public static function cachePrefix(?string $prefix = null)
    {
        return Globals::cachePrefix($prefix);
    }

    public static function defaultAuthServerNode(?string $host = null)
    {
        return Globals::primaryServer($host);
    }

    /**
     * Set/Get the name of the HTTP guard instance.
     *
     * @return string
     */
    public static function guard(?string $name = null)
    {
        return Globals::guard($name);
    }

    /**
     * Set the path where caches data are written when using array store provider.
     *
     * @return string
     */
    public static function cachePath(?string $path = null)
    {
        return Globals::cachePath($path);
    }
}
