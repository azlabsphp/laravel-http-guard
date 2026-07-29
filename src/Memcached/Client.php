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

namespace Drewlabs\HttpGuard\Memcached;

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException;

class Client implements AuthenticatableCacheProvider
{
    /** @var \Memcached */
    private $client;

    /** @var string */
    private $prefix;

    /**
     * creates a memcached provider instance
     * 
     * @param Memcached $client 
     * @param array $options
     * 
     * @return void 
     */
    public function __construct(\Memcached $client, array $options = [])
    {
        $this->client = $client;
        $this->prefix = $options['cache_prefix'] ?? 'http_guard_';
    }

    public function write(string $id, Authenticatable $user)
    {
        if ($this->exists($id)) {
            $this->delete($id);
        }

        $this->client->add($this->resolveKey($id), serialize($user));
    }

    public function read(string $id): ?Authenticatable
    {
        $serialized = $this->client->get($this->resolveKey($id));
        if (false === $serialized) {
            throw new AuthenticatableNotFoundException($id);
        }

        return unserialize($serialized);
    }

    public function delete(string $id)
    {
        $this->client->delete($this->resolveKey($id));
    }

    public function prune()
    {
    }

    private function exists(string $key)
    {
        $this->client->get($this->resolveKey($key));

        return \Memcached::RES_NOTFOUND !== $this->client->getResultCode();
    }

    private function resolveKey(string $key)
    {
        return $this->prefix.sha1($key);
    }
}
