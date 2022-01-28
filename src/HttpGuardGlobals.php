<?php

namespace Drewlabs\AuthHttpGuard;

use ArrayIterator;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Iter;
use InvalidArgumentException;

class HttpGuardGlobals
{
    /**
     * 
     * @var string
     */
    private static $AVAILABLE_NODE_SERVER_CACHE_PATH = __DIR__ . '/../cache/node.sock';

    /**
     * 
     * @var array<string,string|array<int,string>>
     */
    private static $AUTH_SERVER_NODES = [
        [
            'host' => 'http://localhost:4300',
            'primary' => true
        ],
        [
            'host' => 'http://localhost:8000',
            'primary' => false
        ],
        [
            'host' => 'http://localhost:8888',
            'primary' => false
        ]
    ];

    /**
     * Route to users resource
     * 
     * @var string
     */
    private static $USER_PATH = '/auth/v2/user';

    /**
     * Route to users resource
     * 
     * @var string
     */
    private static $LOGOUT_PATH = '/auth/v2/logout';

    /**
     * 
     * @var bool
     */
    private static $USE_CACHE = true;

    /**
     * 
     * @var array<string|string|int>
     */
    private static $REDIS_CONFIG = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'connectTimeout' => 2.5,
        'ssl' => [
            'verify_peer' => false
        ],
    ];

    /**
     * 
     * @var string
     */
    private static $GUARD_DRIVER = 'http';

    /**
     * 
     * @var string
     */
    private static $AUTHENTICATABLE_CLASS = User::class;

    /**
     * 
     * @param array<int,array<string,string|bool>> $nodes 
     * @return array<int,array<string,string|bool>> 
     */
    public static function hosts($nodes = [])
    {
        if (!empty($nodes)) {
            // TODO : Make sure only a primary node is defined
            $nodes = array_filter($nodes, function ($node) {
                return is_array($node) && isset($node['host']);
            });
            $count  = iterator_count(Iter::filter(new ArrayIterator($nodes), function ($node) {
                return true === $node['primary'] ?? false;
            }));
            if ($count !== 1) {
                throw new InvalidArgumentException('Auth Servers cluster nodes must contains only one primary node');
            }
            static::$AUTH_SERVER_NODES = $nodes;
        }
        return static::$AUTH_SERVER_NODES;
    }

    /**
     * 
     * @param string|null $path 
     * @return string 
     */
    public static function userPath(string $path = null)
    {
        if ($path) {
            static::$USER_PATH = $path;
        }
        return static::$USER_PATH;
    }

    /**
     * 
     * @param string|null $path 
     * @return string 
     */
    public static function revokePath(string $path = null)
    {
        if ($path) {
            static::$LOGOUT_PATH = $path;
        }
        return static::$LOGOUT_PATH;
    }

    /**
     * 
     * @param bool|null $value 
     * @return bool 
     */
    public static function usesCache(bool $value = null)
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
     * 
     * @param array|string $options 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public static function forRedis($options = [])
    {
        if (is_string($options)) {
            if (false === parse_url($options)) {
                throw new InvalidArgumentException("No valid uri scheme provided for redis connection");
            }
            static::$REDIS_CONFIG = $options;
        }
        if (is_array($options)) {
            static::$REDIS_CONFIG = array_merge(static::$REDIS_CONFIG, Arr::filterNull($options ?? []));
        }
    }

    /**
     * 
     * @return (string|int)[] 
     */
    public static function redis()
    {
        return static::$REDIS_CONFIG;
    }

    /**
     * 
     * @param string|array<int,string>|null $guard 
     * @return string 
     */
    public static function guards($guard = null)
    {
        if (null !== $guard) {
            static::$GUARD_DRIVER = $guard;
        }
        return static::$GUARD_DRIVER;
    }

    public static function authenticatableClass(string $authClass = null)
    {
        if (null !== $authClass) {
            static::$AUTHENTICATABLE_CLASS = $authClass;
        }
        return static::$AUTHENTICATABLE_CLASS;
    }
}
