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

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Contracts\Auth\AuthorizationsAware;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\HttpGuard\Traits\AttributesAware;
use Drewlabs\HttpGuard\Traits\Authenticatable as AuthTrait;
use Drewlabs\HttpGuard\Traits\Authorizable;
use Drewlabs\HttpGuard\Traits\ContainerAware;
use Drewlabs\HttpGuard\Traits\HasApiToken;
use Illuminate\Contracts\Auth\Authenticatable as AbstractAuthenticatable;

class User implements Authenticatable, AuthorizationsAware, AbstractAuthenticatable, HasApiTokens
{
    use AttributesAware;
    use Authorizable;
    use AuthTrait;
    use ContainerAware;
    use HasApiToken;

    private function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        $this->validateAttributes();
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

    public function validateAttributes()
    {
        $passes = null !== $this->getAuthIdentifier() && $this->isVerified() && null !== $this->getAuthUserName();
        if (!$passes) {
            throw new ServerBadResponseException('missing required attributes');
        }
    }

    public function getAuthIdentifierName()
    {
        return $this->authIdentifierName();
    }

    public function rememberTokenName()
    {
        return Globals::rememberTokenProperty();
    }

    public function getRememberTokenName()
    {
        return $this->rememberTokenName();
    }

    public function authIdentifierName()
    {
        return Globals::userIdProperty();
    }


    public function authPasswordName()
    {
        return Globals::passwordProperty();
    }

    public function getAuthPasswordName()
    {
        return $this->authPasswordName();
    }

    public function getAuthUserName()
    {
        return $this->getAttribute(Globals::usernameProperty());
    }

    public function getAuthorizations(): array
    {
        return $this->getAttribute(Globals::authorizationsProperty()) ?? [];
    }

    public function getAuthorizationGroups(): array
    {
        return $this->getAttribute(Globals::groupsProperty()) ?? [];
    }

    public function setAuthorizations(array $value = [])
    {
        $this->setAttribute(Globals::authorizationsProperty(), $value);
    }

    public function setAuthorizationGroups(array $value = [])
    {
        $this->setAttribute(Globals::groupsProperty(), $value);
    }
}
