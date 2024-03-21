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

use Drewlabs\Contracts\OAuth\Token;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\DateTime;
use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Traits\AttributesAware;
use Drewlabs\HttpGuard\Traits\ContainerAware;

class AccessToken implements Token
{
    use AttributesAware;
    use ContainerAware;

    /**
     * Creates class instance.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function revoke()
    {
        if ($this->authToken) {
            try {
                $provider = static::createResolver(ApiTokenAuthenticatableProvider::class)() ?? new AuthenticatableProvider();
            } catch (\Throwable $th) {
                $provider = new AuthenticatableProvider();
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

        return \in_array('*', $abilities, true)
            || \array_key_exists($ability, array_flip($abilities));
    }

    public function cant($ability)
    {
        return !$this->can($ability);
    }

    /**
     * Checks whether the token has expire.
     *
     * @return bool
     */
    public function expires()
    {
        $expiresAt = $this->expiresAt();
        if (null === $expiresAt) {
            return true;
        }

        return !DateTime::isfuture(new \DateTimeImmutable($expiresAt));
    }

    /**
     * Returns the expiration date of the token.
     *
     * @return ?string
     */
    public function expiresAt()
    {
        return $this->expires_at ?? ($this->expiresAt ?? null);
    }

    public function setAccessToken($value)
    {
        Arr::set($this->attributes, 'authToken', $value);
    }
}
