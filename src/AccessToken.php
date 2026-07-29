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

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\DateTime;
use Drewlabs\HttpGuard\Contracts\Token;
use Drewlabs\HttpGuard\Facades\LegacyAuthProvider;
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
            return LegacyAuthProvider::revokeOAuthToken($this->authToken);
        }
    }

    public function transient()
    {
        return false;
    }

    #[\Override]
    public function getScopes(): array
    {
        return $this->scopes ?? [];
    }

    #[\Override]
    public function can($ability)
    {
        $abilities = $this->getScopes();

        return \in_array('*', $abilities, true)
            || \array_key_exists($ability, array_flip($abilities));
    }

    #[\Override]
    public function cant($ability)
    {
        return !$this->can($ability);
    }


    #[\Override]
    public function expires(): bool
    {
        ;
        if (is_null($expiresAt = $this->expiresAt())) {
            return true;
        }

        return !DateTime::isfuture(new \DateTimeImmutable($expiresAt));
    }

    /**
     * returns the expiration date of the token.
     *
     * @return ?string
     */
    private function expiresAt()
    {
        return $this->expires_at ?? ($this->expiresAt ?? null);
    }

    /**
     * set the authorization token property value
     * 
     * @param string $value
     * 
     * @return static 
     */
    public function setAccessToken($value)
    {
        Arr::set($this->attributes, 'authToken', $value);

        return $this;
    }
}
