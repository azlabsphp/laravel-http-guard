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

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException;

class ArrayCacheProvider implements AuthenticatableCacheProvider
{
    /** @var array<string,Authenticatable> */
    private $state = [];

    /** @var string */
    private $prefix;


    /** @var string */
    private $writePath;

    /**
     * creates the Array Cache provider instances.
     *
     * @param array $state
     *
     * @return void
     */
    private function __construct($state = [], array $options = [])
    {
        $this->state = $state ?? [];
        $this->prefix = $options['cache_prefix'] ?? 'http_guard_';
        $this->writePath = $options['write_path'];
    }

    public function write(string $id, Authenticatable $user)
    {
        $this->state[$this->resolveKey($id)] = $user;
    }

    public function read(string $id): ?Authenticatable
    {
        $id = $this->resolveKey($id);
        if (!\array_key_exists($id, $this->state ?? [])) {
            throw new AuthenticatableNotFoundException($id);
        }

        return $this->state[$id];
    }

    public function delete(string $id)
    {
        unset($this->state[$this->resolveKey($id)]);
    }

    public function prune()
    {
        foreach ($this->state ?? [] as $key => $value) {
            if (($value instanceof User) && $value->tokenExpires()) {
                unset($this->state[$key]);
            }
        }
    }

    public function getState()
    {
        return $this->state ?? [];
    }

    public function mergeState(array $state)
    {
        $this->state = array_merge($this->state ?? [], $state);

        return $this;
    }

    public static function dump(self $cache)
    {
        $dirname = \dirname($cache->writePath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        if ($result = @serialize($cache->mergeState(self::load($cache->writePath)->getState()))) {
            ReadWriter::open($cache->writePath, 'wb')->write($result);
        }
    }

    /**
     * @return static
     */
    public static function load(string $path)
    {
        if (!file_exists($path)) {
            return new self();
        }

        $contents = ReadWriter::open($path)->read();

        $value = false !== $contents && !empty($contents) ? @unserialize($contents) : false;

        return $value && $value instanceof self ? $value : new self();
    }


    public function __destruct()
    {
        ArrayCacheProvider::dump($this);
    }

    private function resolveKey(string $key)
    {
        return $this->prefix.sha1($key);
    }
}
