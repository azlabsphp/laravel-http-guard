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

namespace Drewlabs\HttpGuard\Traits;


/**
 * @deprecated
 * @mixin AttributesAware
 */
trait Authenticatable
{
    public function getAuthIdentifier()
    {
        return $this->authIdentifier();
    }

    public function getAuthPassword()
    {
        return $this->authPassword();
    }

    public function getRememberToken()
    {
        return $this->rememberToken();
    }

    /** 
     * @param string $value
     */
    public function setRememberToken($value)
    {
        return $this->rememberToken($value);
    }

    public function authIdentifier()
    {
        return (string) $this->getAttribute($this->authIdentifierName());
    }

    public function authPassword()
    {
        return $this->getAttribute($this->authPasswordName());
    }

    public function rememberToken($token = null)
    {
        if (null === $token) {
            return $this->getAttribute($this->rememberTokenName());
        }

        $this->setAttribute($this->rememberTokenName(), $token);
    }
}
