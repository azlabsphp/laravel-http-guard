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
use Drewlabs\Contracts\Auth\OpenIDUserInterface;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;
use Drewlabs\HttpGuard\Contracts\Token;


final class User implements HasApiTokens, Authenticatable, AuthorizationsAware, BaseAuthenticatable, OpenIDUserInterface
{
    use HasApiToken;
    use Authorizable;
    use AttributesAware;

    /** @var Token */
    private $accessToken;

    /** @var string|int */
    private $id;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $email;

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
     * @param string|null $name 
     * @param string|null $email
     * @param null|string $password
     * 
     * @return void 
     */
    public function __construct($sub, ?string $name = null, ?string $email = null, ?string $password  = null, array $authorizations = [], array $groups = [], array $attributes = [])
    {
        $this->id = $sub;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->authorizations = $authorizations;
        $this->groups = $groups;

        $this->setAttributes($attributes ?? []);
    }

    #[Override]
    public function getSubject()
    {
        return $this->id;
    }

    #[Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    #[Override]
    public function getEmail(): ?string
    {
        return $this->email;
    }

    #[Override]
    public function getClaims(): array
    {
        return $this->attributes;
    }

    #[Override]
    public function getClaim(string $key, $default = null)
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getScopes(): array
    {
        if ($accessToken = $this->accessToken) {
            return $accessToken->getScopes();
        }

        return [];
    }

    #[Override]
    public function hasScope(string $scope): bool
    {
        if ($accessToken = $this->accessToken) {
            return $accessToken->can($scope);
        }

        return false;
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
        return $this->name;
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