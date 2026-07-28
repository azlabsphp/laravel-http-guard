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

/** @deprecated implementations does not depends on globals anymore */
class Globals
{
    /**
     * @var string
     */
    private static $nodesCachePath = __DIR__.'/../cache/node.sock';

    /**
     * Path serialized connected users are cached.
     * 
     * @var string
     */
    private static $cachePath = __DIR__.'/../cache/auth.dump';

    /**
     * @var array<string,string|array<int,string>>
     */
    private static $clusterServers = [];

    /**
     * @var string
     */
    private static $primaryServer;

    /**
     * route to users resource.
     *
     * @var string
     */
    private static $apiMe = '/api/v2/user';

    /**
     * route to users resource.
     *
     * @var string
     */
    private static $apiLogout = '/api/v2/logout';

    /**
     * @var bool
     */
    private static $useCache = true;

    /**
     * @var array<string|string|int>
     */
    private static $redis = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'connectTimeout' => 2.5,
        'ssl' => [ 'verify_peer' => false ],
    ];

    /**
     * @var string
     */
    private static $guards = 'web';

    /**
     * @var string
     */
    private static $authenticatable = LegacyUser::class;

    /**
     * possible values array|redis|memcahed.
     *
     * @var string
     */
    private static $cacheDriver = 'array';

    /**
     * @var (array|null)[]
     */
    private static $memcached = [
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
    private static $cachePrefix = 'drewlabs_http_guard_';

    /**
     * @var string
     */
    private static $guardName = 'http';


    /** @var string */
    private static $accessTokenProperty = 'accessToken';

    /** @var string */
    private static $usernameProperty = 'username';

    /** @var string */
    private static $userIdProperty = 'id';

    /** @var string */
    private static $rememberTokenProperty = 'remember_token';

    /** @var string */
    private static $passwordProperty = 'password';

    /** @var string */
    private static $authorizationsProperty = 'authorizations';

    /** @var string */
    private static $groupsProperty = 'roles';

    /**
     * @param array<int,array<string,string|bool>> $nodes
     *
     * @return array<int,array<string,string|bool>>
     */
    public static function hosts($nodes = [])
    {
        if (!empty($nodes)) {
            $nodes = array_filter($nodes, static function ($node) {
                return \is_array($node) && isset($node['host']);
            });
            $count = iterator_count(Iter::filter(new \ArrayIterator($nodes), static function ($node) {
                return true === $node['primary'] ?? false;
            }));
            if (1 !== $count) {
                throw new \InvalidArgumentException('Auth Servers cluster nodes must contains only one primary node');
            }
            static::$clusterServers = $nodes;
        }

        return static::$clusterServers;
    }

    /**
     * @return string
     */
    public static function userPath(?string $path = null)
    {
        if ($path) {
            static::$apiMe = $path;
        }

        return static::$apiMe;
    }

    /**
     * @return string
     */
    public static function revokeTokenPath(?string $path = null)
    {
        if ($path) {
            static::$apiLogout = $path;
        }

        return static::$apiLogout;
    }

    /**
     * @return bool
     */
    public static function usesCache(?bool $value = null)
    {
        if (null !== $value) {
            static::$useCache = $value;
        }

        return static::$useCache;
    }

    public static function nodesCachePath(?string $path = null)
    {
        if (null !== $path) {
            static::$nodesCachePath = $path;
        }

        return static::$nodesCachePath;
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
            static::$redis = $options;
        }
        if (\is_array($options)) {
            static::$redis = array_merge(static::$redis, Arr::filterNull($options ?? []));
        }

        return static::$redis;
    }

    /**
     * @return (string|int)[]
     */
    public static function redis()
    {
        return static::$redis;
    }

    /**
     * @param string|null $guard
     *
     * @return string
     */
    public static function defaultGuards($guard = null)
    {
        if (null !== $guard) {
            static::$guards = $guard;
        }

        return static::$guards;
    }

    public static function authenticatable(?string $name = null)
    {
        if (null !== $name) {
            static::$authenticatable = $name;
        }

        return static::$authenticatable;
    }

    public static function useCacheDriver(string $driver)
    {
        static::$cacheDriver = $driver;
    }

    public static function getCacheDriver()
    {
        return static::$cacheDriver;
    }

    public static function forMemcached(?array $config = null)
    {
        if (\is_array($config) && !empty($config)) {
            static::$memcached = $config;
        }

        return static::$memcached;
    }

    public static function cachePrefix(?string $prefix = null)
    {
        if (null !== $prefix) {
            static::$cachePrefix = $prefix;
        }

        return static::$cachePrefix;
    }

    public static function primaryServer(?string $host = null)
    {
        if (null !== $host) {
            static::$primaryServer = $host;
        }

        return static::$primaryServer;
    }

    /**
     * Set/Get the name of the HTTP guard instance.
     *
     * @return string
     */
    public static function guard(?string $name = null)
    {
        if (null !== $name) {
            static::$guardName = $name;
        }

        return static::$guardName;
    }

    /**
     * set the path where caches data are written when using array store provider.
     *
     * @return string
     */
    public static function cachePath(?string $path = null)
    {
        if (null !== $path) {
            self::$cachePath = $path;
        }

        return self::$cachePath;
    }

    /**
     * `accessToken` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function accessTokenProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$accessTokenProperty = $value;
        }

        return self::$accessTokenProperty;
    }

    /**
     * `username` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function usernameProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$usernameProperty = $value;
        }

        return self::$usernameProperty;
    }

    /**
     * `userId` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function userIdProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$userIdProperty = $value;
        }

        return self::$userIdProperty;
    }

    /**
     * `remember token` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function rememberTokenProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$rememberTokenProperty = $value;
        }

        return self::$rememberTokenProperty;
    }

    /**
     * `password` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function passwordProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$passwordProperty = $value;
        }

        return self::$passwordProperty;
    }

    /**
     * `authorizations` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function authorizationsProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$authorizationsProperty = $value;
        }

        return self::$authorizationsProperty;
    }

    /**
     * `groups` property configuration
     * 
     * @param null|string $value
     * 
     * @return string 
     */
    public static function groupsProperty(?string $value = null)
    {
        if (null !== $value) {
            self::$groupsProperty = $value;
        }

        return self::$groupsProperty;
    }
}
