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

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Iter;

class HttpGuardGlobals
{
    /**
     * @var string
     */
    private static $AVAILABLE_NODE_SERVER_CACHE_PATH = __DIR__.'/../cache/node.sock';

    /**
     * @var array<string,string|array<int,string>>
     */
    private static $AUTH_SERVERS_CLUSTER = [];

    /**
     * @var string
     */
    private static $DEFAULT_AUTH_SERVER_NODE;

    /**
     * Route to users resource.
     *
     * @var string
     */
    private static $USER_PATH = '/api/v2/user';

    /**
     * Route to users resource.
     *
     * @var string
     */
    private static $LOGOUT_PATH = '/api/v2/logout';

    /**
     * @var bool
     */
    private static $USE_CACHE = true;

    /**
     * Path serialized connected users are cached.
     */
    private static $CACHE_PATH = __DIR__.'/../cache/auth.dump';

    /**
     * @var array<string|string|int>
     */
    private static $REDIS_CONFIG = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'connectTimeout' => 2.5,
        'ssl' => [
            'verify_peer' => false,
        ],
    ];

    /**
     * @var string
     */
    private static $DEFAULT_GUARDS = 'web';

    /**
     * @var string
     */
    private static $AUTHENTICATABLE = User::class;

    /**
     * Possible values array|redis|memcahed(not supported yet).
     *
     * @var string
     */
    private static $DEFAULT_CACHE_DRIVER = 'array';

    /**
     * @var (array|null)[]
     */
    private static $MEMCACHED_CONFIG = [
        'persistent_id' => null,
        'options' => [/* \Memcached::OPT_CONNECT_TIMEOUT => 2000, */],
        'servers' => [
            [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 100,
            ],
        ],
    ];

    /**
     * @var string
     */
    private static $CACHE_PREFIX = 'drewlabs_http_guard_';

    /**
     * @var string
     */
    private static $GUARD = 'http';

    /**
     * @param array<int,array<string,string|bool>> $nodes
     *
     * @return array<int,array<string,string|bool>>
     */
    public static function hosts($nodes = [])
    {
        if (!empty($nodes)) {
            // TODO : Make sure only a primary node is defined
            $nodes = array_filter($nodes, static function ($node) {
                return \is_array($node) && isset($node['host']);
            });
            $count = iterator_count(Iter::filter(new \ArrayIterator($nodes), static function ($node) {
                return true === $node['primary'] ?? false;
            }));
            if (1 !== $count) {
                throw new \InvalidArgumentException('Auth Servers cluster nodes must contains only one primary node');
            }
            static::$AUTH_SERVERS_CLUSTER = $nodes;
        }

        return static::$AUTH_SERVERS_CLUSTER;
    }

    /**
     * @return string
     */
    public static function userPath(?string $path = null)
    {
        if ($path) {
            static::$USER_PATH = $path;
        }

        return static::$USER_PATH;
    }

    /**
     * @return string
     */
    public static function revokePath(?string $path = null)
    {
        if ($path) {
            static::$LOGOUT_PATH = $path;
        }

        return static::$LOGOUT_PATH;
    }

    /**
     * @return bool
     */
    public static function usesCache(?bool $value = null)
    {
        if (null !== $value) {
            static::$USE_CACHE = $value;
        }

        return static::$USE_CACHE;
    }

    public static function nodeServerCachePath(?string $path = null)
    {
        if (null !== $path) {
            static::$AVAILABLE_NODE_SERVER_CACHE_PATH = $path;
        }

        return static::$AVAILABLE_NODE_SERVER_CACHE_PATH;
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
        if (\is_string($options)) {
            if (false === parse_url($options)) {
                throw new \InvalidArgumentException('No valid uri scheme provided for redis connection');
            }
            static::$REDIS_CONFIG = $options;
        }
        if (\is_array($options)) {
            static::$REDIS_CONFIG = array_merge(static::$REDIS_CONFIG, Arr::filterNull($options ?? []));
        }

        return static::$REDIS_CONFIG;
    }

    /**
     * @return (string|int)[]
     */
    public static function redis()
    {
        return static::$REDIS_CONFIG;
    }

    /**
     * @param string|null $guard
     *
     * @return string
     */
    public static function defaultGuards($guard = null)
    {
        if (null !== $guard) {
            static::$DEFAULT_GUARDS = $guard;
        }

        return static::$DEFAULT_GUARDS;
    }

    public static function authenticatable(?string $name = null)
    {
        if (null !== $name) {
            static::$AUTHENTICATABLE = $name;
        }

        return static::$AUTHENTICATABLE;
    }

    public static function useCacheDriver(string $driver)
    {
        static::$DEFAULT_CACHE_DRIVER = $driver;
    }

    public static function defaultCacheDriver()
    {
        return static::$DEFAULT_CACHE_DRIVER;
    }

    public static function forMemcached(?array $config = null)
    {
        if (\is_array($config) && !empty($config)) {
            static::$MEMCACHED_CONFIG = $config;
        }

        return static::$MEMCACHED_CONFIG;
    }

    public static function cachePrefix(?string $prefix = null)
    {
        if (null !== $prefix) {
            static::$CACHE_PREFIX = $prefix;
        }

        return static::$CACHE_PREFIX;
    }

    public static function defaultAuthServerNode(?string $host = null)
    {
        if (null !== $host) {
            static::$DEFAULT_AUTH_SERVER_NODE = $host;
        }

        return static::$DEFAULT_AUTH_SERVER_NODE;
    }

    /**
     * Set/Get the name of the HTTP guard instance.
     *
     * @return string
     */
    public static function guard(?string $name = null)
    {
        if (null !== $name) {
            static::$GUARD = $name;
        }

        return static::$GUARD;
    }

    /**
     * Set the path where caches data are written when using array store provider.
     *
     * @return string
     */
    public static function cachePath(?string $path = null)
    {
        if (null !== $path) {
            self::$CACHE_PATH = $path;
        }

        return self::$CACHE_PATH;
    }
}
