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

use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\HttpGuard\Traits\HasApiToken;
use Drewlabs\Contracts\Auth\AuthorizationsAware;
use Drewlabs\HttpGuard\Traits\AttributesAware;
use Drewlabs\HttpGuard\Traits\Authorizable;
use Override;
use Drewlabs\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;


final class User implements HasApiTokens, Authenticatable, AuthorizationsAware, BaseAuthenticatable
{
    use HasApiToken;
    use Authorizable;
    use AttributesAware;

    /** @var AccessToken */
    private $accessToken;

    /** @var string|int */
    private $id;

    /** @var string */
    private $username;

    /** @var string */
    private $remember = null;

    /** @var string|null */
    private $password;

    /** @var string[] */
    private $authorizations;

    /** @var string[] */
    private $groups;

    /**
     * creates a user class instance
     * 
     * @param string|int $sub 
     * @param string $name 
     * @param null|string $password
     * 
     * @return void 
     */
    public function __construct($sub, string $name, ?string $password  = null, array $authorizations = [], array $groups = [], array $attributes = [])
    {
        $this->id = $sub;
        $this->username = $name;
        $this->password = $password;
        $this->authorizations = $authorizations;
        $this->groups = $groups;

        $this->setAttributes($attributes);
    }

    /**
     * returns a boolean value indicationg whether the user is verified / Not.
     *
     * @return bool
     */
    public function isVerified()
    {
        return (bool) $this->is_verified || (bool) $this->isVerified;
    }

    public function tokenExpires()
    {
        if (!\is_object($this->accessToken)) {
            return true;
        }

        return $this->accessToken->expires();
    }

    public function tokenExpiresAt()
    {
        return $this->accessToken->expiresAt();
    }

    #[Override]
    public function getAuthIdentifierName()
    {
        return $this->authIdentifierName();
    }

    #[Override]
    public function getAuthPasswordName()
    {
        return $this->authPasswordName();
    }

    #[Override]
    public function getAuthUserName()
    {
        return $this->username;
    }

    #[Override]
    public function getAuthorizations(): array
    {
        return $this->authorizations;
    }

    #[Override]
    public function getAuthorizationGroups(): array
    {
        return $this->groups;
    }

    public function authIdentifier()
    {
        return (string) $this->id;
    }

    public function getAuthIdentifier()
    {
        return $this->authIdentifier();
    }

    public function authPassword()
    {
        return $this->password;
    }


    public function getAuthPassword()
    {
        return $this->authPassword();
    }

    public function rememberToken($token = null)
    {
        if (null === $token) {
            return $this->remember;
        }

        $this->remember = $token;
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


    #[Override]
    public function getRememberTokenName()
    {
        return $this->rememberTokenName();
    }

    #[Override]
    public function authIdentifierName()
    {
        return 'id';
    }

    #[Override]
    public function authPasswordName()
    {
        return 'password';
    }

    #[Override]
    public function rememberTokenName()
    {
        return 'remember_token';
    }
}