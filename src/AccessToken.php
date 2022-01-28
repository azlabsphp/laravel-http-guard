<?php

namespace Drewlabs\AuthHttpGuard;

use DateTimeImmutable;
use Drewlabs\AuthHttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\AuthHttpGuard\Traits\ContainerAware;
use Drewlabs\Contracts\OAuth\Token;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Support\Traits\AttributesAware;

/** @package Drewlabs\AuthHttpGuard */
class AccessToken implements Token
{
    use AttributesAware, ContainerAware;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function revoke()
    {
        if ($this->authToken) {
            try {
                /**
                 * @var ApiTokenAuthenticatableProvider
                 */
                $provider = static::createResolver(ApiTokenAuthenticatableProvider::class)() ?? new AuthenticatableProvider;
            } catch (\Throwable $th) {
                $provider = new AuthenticatableProvider;
            }
            $provider->revokeOAuthToken($this->authToken);
        }
    }

    public function transient()
    {
        return false;
    }

    public function abilities()
    {
        return $this->scopes ?? [];
    }

    public function can($ability)
    {
        $abilities = $this->abilities();
        return in_array('*', $abilities) ||
            array_key_exists($ability, array_flip($abilities));
    }

    public function cant($ability)
    {
        return !$this->can($ability);
    }

    public function expires()
    {
        $expires_at = $this->expiresAt();
        if (null === $expires_at) {
            return true;
        }
        return !drewlabs_core_datetime_is_future(new DateTimeImmutable($expires_at));
    }

    public function expiresAt()
    {
        return $this->expires_at;
    }

    public function setAccessToken($value)
    {
        Arr::set($this->attributes, 'authToken', $value);
    }
}
