<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Traits\Authenticatable as TraitsAuthenticatable;
use Drewlabs\AuthHttpGuard\Traits\Authorizable;
use Drewlabs\AuthHttpGuard\Traits\HasApiToken;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Contracts\Auth\AuthorizableInterface;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Support\Traits\AttributesAware;
use Illuminate\Contracts\Auth\Authenticatable as AuthAuthenticatable;

/** @package Drewlabs\AuthHttpGuard */
class User implements
    Authenticatable,
    AuthorizableInterface,
    AuthAuthenticatable,
    HasApiTokens
{
    use AttributesAware, HasApiToken, Authorizable, TraitsAuthenticatable;

    private function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function tokenExpires()
    {
        return $this->accessToken->expires();
    }

    public function tokenExpiresAt()
    {
        return $this->accessToken->expiresAt();
    }
}
