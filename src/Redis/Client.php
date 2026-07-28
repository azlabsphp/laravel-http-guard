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

namespace Drewlabs\HttpGuard\Redis;

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Core\Helpers\DateTime;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException;
use Predis\Client as Redis;

class Client implements AuthenticatableCacheProvider
{
    /** @var Redis */
    private $client;

    /** @var string */
    private $prefix;

    /**
     * creates a redis cache provider instance
     * 
     * @param array $options
     * 
     * @return void 
     */
    public function __construct(array $options)
    {
        $this->client = new Redis($options);
        $this->prefix = $options['cache_prefix'] ?? 'http_guard_';
    }

    public function write(string $id, Authenticatable $user)
    {
        $id = $this->resolveKey($id);
        if ($this->client->exists($id)) {
            $this->client->del($id);
        }
        $expiresAt = $user instanceof LegacyUser ? new \DateTimeImmutable($user->tokenExpiresAt()) : null;
        $expires = $expiresAt ? DateTime::secsDiff($expiresAt, DateTime::now()) : null;
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
