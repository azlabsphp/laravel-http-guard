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

class CacheProvider implements AuthenticatableCacheProvider
{
    /** @var array<string,Authenticatable> */
    private $state = [];

    /** @var string */
    private $prefix;


    /** @var string */
    private $path;

    /**
     * creates the array cache provider instances.
     *
     * @param array $state
     *
     * @return void
     */
    private function __construct($state = [], array $options = [])
    {
        $this->state = $state ?? [];
        $this->prefix = $options['cache_prefix'] ?? 'http_guard_';
        $this->path = $options['write_path'] ?? __DIR__ . '/../cache/auth.dump';
    }

    public function write(string $id, Authenticatable $user)
    {
        $this->state[$this->key($id)] = $user;
    }

    public function read(string $id): ?Authenticatable
    {
        $id = $this->key($id);
        if (!\array_key_exists($id, $this->state ?? [])) {
            throw new AuthenticatableNotFoundException($id);
        }

        return $this->state[$id];
    }

    public function delete(string $id)
    {
        unset($this->state[$this->key($id)]);
    }

    public function prune()
    {
        foreach ($this->state ?? [] as $key => $value) {
            if (($value instanceof User) && $value->tokenExpires()) {
                unset($this->state[$key]);
            }
        }
    }

    /**
     * return current state of the cache
     * 
     * @return array<string,Authenticatable> 
     */
    public function getState()
    {
        return $this->state ?? [];
    }

    /**
     * return the path to cache state on disk
     * 
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    public function mergeState(array $state)
    {
        $this->state = array_merge($this->state ?? [], $state);

        return $this;
    }

    public static function dump(array $state, string $path)
    {
        $dirname = \dirname($path);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        if ($result = @serialize($state)) {
            ReadWriter::open($path, 'wb')->write($result);
        }
    }

    /**
     * @return static
     */
    public static function load(string $path)
    {
        if (!file_exists($path)) {
            return new self([], ['write_path' => $path]);
        }

        $contents = ReadWriter::open($path)->read();

        $value = false !== $contents && !empty($contents) ? @unserialize($contents) : false;

        return new self($value && is_array($value) ? $value : [], ['write_path' => $path]);
    }

    public function __destruct()
    {
        CacheProvider::dump($this->state, $this->path);
    }

    private function key(string $key)
    {
        return $this->prefix.sha1($key);
    }
}
