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

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\AuthHttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\Contracts\Auth\Authenticatable;

class ArrayCacheProvider implements AuthenticatableCacheProvider
{
    /**
     * @var array<string,Authenticatable>
     */
    private $state = [];

    /**
     * @var string
     */
    private $prefix;

    /**
     * Creates the Array Cache provider instances.
     *
     * @param array $state
     *
     * @return void
     */
    private function __construct($state = [])
    {
        $this->state = $state ?? [];
        $this->prefix = HttpGuardGlobals::cachePrefix();
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
            if (($value instanceof User) && ($value->tokenExpires())) {
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
        if (!HttpGuardGlobals::usesCache()) {
            return;
        }
        $path = HttpGuardGlobals::cachePath();
        $dirname = dirname($path);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        if ($result = @serialize($cache->mergeState(self::load()->getState()))) {
            ReadWriter::open($path, 'wb')->write($result);
        }
    }

    /**
     * @return self
     */
    public static function load()
    {
        if (!HttpGuardGlobals::usesCache()) {
            return new self();
        }
        if (!file_exists($path = HttpGuardGlobals::cachePath())) {
            return new self();
        }

        $contents = ReadWriter::open($path)->read();

        $deserialized = false !== $contents && !empty($contents) ? @unserialize($contents) : false;

        return $deserialized && $deserialized instanceof self ? $deserialized : new self();
    }

    private function resolveKey(string $key)
    {
        return $this->prefix.sha1($key);
    }
}
