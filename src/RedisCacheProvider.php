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
use Drewlabs\Core\Helpers\ImmutableDateTime;
use Predis\Client;

class RedisCacheProvider implements AuthenticatableCacheProvider
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $prefix;

    public function __construct()
    {
        try {
            $this->client = new Client(HttpGuardGlobals::redis());
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
        $this->prefix = HttpGuardGlobals::cachePrefix();
    }

    public function write(string $id, Authenticatable $user)
    {
        $id = $this->resolveKey($id);
        if ($this->client->exists($id)) {
            $this->client->del($id);
        }
        $expiresAt = $user instanceof User ? new \DateTimeImmutable($user->tokenExpiresAt()) : null;
        $expires = $expiresAt ? ImmutableDateTime::secsDiff($expiresAt, ImmutableDateTime::now()) : null;
        if ($expires && ($expires <= 0)) {
            return;
        }
        $this->client->set($id, serialize($user));
        $this->client->expire($id, $expires);
    }

    public function read(string $id): Authenticatable
    {
        $id = $this->resolveKey($id);
        if (!$this->client->exists($id)) {
            throw new AuthenticatableNotFoundException($id);
        }

        return unserialize($this->client->get($id));
    }

    public function delete(string $id)
    {
        $id = $this->resolveKey($id);
        $this->client->del($id);
    }

    public function prune()
    {
    }

    private function resolveKey(string $key)
    {
        return $this->prefix.sha1($key);
    }
}
