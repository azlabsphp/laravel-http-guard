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

namespace Drewlabs\HttpGuard\Traits;

trait Authenticatable
{
    public function getAuthIdentifierName()
    {
        return $this->authIdentifierName();
    }

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

    public function setRememberToken($value)
    {
        return $this->rememberToken($value);
    }

    public function getRememberTokenName()
    {
        return $this->rememberTokenName();
    }

    public function authIdentifierName()
    {
        return 'id';
    }

    public function authIdentifier()
    {
        return (string) ($this->__get($this->authIdentifierName()));
    }

    public function authPassword()
    {
        return $this->__get($this->authPasswordName());
    }

    public function authPasswordName()
    {
        return 'password';
    }

    public function rememberToken($token = null)
    {
        if (null === $token) {
            return $this->__get($this->rememberTokenName());
        }
        $this->__set($this->rememberTokenName(), $token);
    }

    public function rememberTokenName()
    {
        return 'remember_token';
    }

    public function getAuthUserName()
    {
        return $this->username;
    }

    public function getUserDetails()
    {
        return $this->user_details;
    }
}
