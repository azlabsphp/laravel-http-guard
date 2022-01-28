<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\AuthHttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\AuthHttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\AuthHttpGuard\User;
use Drewlabs\Contracts\Auth\Authenticatable;

/** @package Drewlabs\AuthHttpGuard\Testing */
class ArrayCacheProvider implements AuthenticatableCacheProvider
{
    /**
     * 
     * @var array<string,Authenticatable>
     */
    private $state = [];

    const CACHE_PATH = __DIR__ . '/../cache/auth.dump';

    private function __construct($state = [])
    {
        $this->state = $state ?? [];
    }

    public function write(string $id, Authenticatable $user)
    {
        $this->state[$id] = $user;
    }

    public function read(string $id): ?Authenticatable
    {
        if (!array_key_exists($id, $this->state ?? [])) {
            throw new AuthenticatableNotFoundException($id);
        }
        $user = $this->state[$id];
        if (($user instanceof User) && ($user->tokenExpires())) {
            throw new TokenExpiresException($id);
        }
        return $user;
    }

    public function delete(string $id)
    {
        unset($this->state[$id]);
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
        $writeCache = function (string $path, ?string $data) {
            $fd = @fopen($path, "wb");
            if ($fd && flock($fd, LOCK_EX | LOCK_NB)) {
                fwrite($fd, $data);
                flock($fd, LOCK_UN);
                @fclose($fd);
            }
            return false;
        };
        $writeCache(
            static::CACHE_PATH,
            serialize(
                $cache->mergeState(self::load()->getState())
            )
        );
    }

    /**
     * 
     * @return self
     */
    public static function load()
    {
        if (!file_exists(static::CACHE_PATH)) {
            return new self;
        }
        $readCache = function ($path) {
            $fd = @fopen($path, "rb");
            if ($fd && flock($fd, LOCK_EX | LOCK_NB)) {
                $contents = fread($fd, filesize($path));
                flock($fd, LOCK_UN);
                @fclose($fd);
                return $contents;
            }
            return false;
        };
        $self =  unserialize($readCache(static::CACHE_PATH)) ?? new self;
        return $self;
    }
}
