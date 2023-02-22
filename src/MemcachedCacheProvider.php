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

class MemcachedCacheProvider implements AuthenticatableCacheProvider
{
    /**
     * @var \Memcached
     */
    private $client;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(\Memcached $client)
    {
        $this->client = $client;
        $this->prefix = HttpGuardGlobals::cachePrefix();
    }

    public function write(string $id, Authenticatable $user)
    {
        $expiresAt = $user instanceof User ? new \DateTimeImmutable($user->tokenExpiresAt()) : null;
        $expiration = $expiresAt ? drewlabs_core_datetime_secs_diff($expiresAt, drewlabs_core_datetime_now()) : null;
        if ($expiration && ($expiration <= 0)) {
            return;
        }
        if ($this->exists($id)) {
            $this->delete($id, serialize($user), $expiration);
        }
        $this->client->add($this->resolveKey($id), serialize($user), $expiration);
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
