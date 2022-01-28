<?php

namespace Drewlabs\AuthHttpGuard;

use DateTimeImmutable;
use Drewlabs\AuthHttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\AuthHttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\AuthHttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\Contracts\Auth\Authenticatable;
use Exception;
use Predis\Client;
use RuntimeException;

/** @package Drewlabs\AuthHttpGuard */
class RedisCacheProvider implements AuthenticatableCacheProvider
{
    /**
     * 
     * @var Client
     */
    private $client;

    public function __construct()
    {
        try {
            $this->client = new Client(HttpGuardGlobals::redis());
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public function write(string $id, Authenticatable $user)
    {
        if ($this->client->exists($id)) {
            $this->client->del($id);
        }
        $expiresAt = $user instanceof User ? new DateTimeImmutable($user->tokenExpiresAt()) : null;
        $diff = $expiresAt ? \drewlabs_core_datetime_secs_diff($expiresAt, drewlabs_core_datetime_now()) : null;
        if ($diff && ($diff < 0)) {
            return;
        }
        $this->client->set($id, serialize($user));
        $this->client->expire($id, $diff);
    }

    public function read(string $id): Authenticatable
    {
        if (!$this->client->exists($id)) {
            throw new AuthenticatableNotFoundException($id);
        }
        $user = unserialize($this->client->get($id));
        if (($user instanceof User) && ($user->tokenExpires())) {
            throw new TokenExpiresException($id);
        }
        return $user;
    }

    public function delete(string $id)
    {
        $this->client->del($id);
    }

    public function prune()
    {
    }
}
