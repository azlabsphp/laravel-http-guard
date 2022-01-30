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

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Traits\Authenticatable as TraitsAuthenticatable;
use Drewlabs\AuthHttpGuard\Traits\Authorizable;
use Drewlabs\AuthHttpGuard\Traits\HasApiToken;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Contracts\Auth\AuthorizableInterface;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Support\Traits\AttributesAware;
use Illuminate\Contracts\Auth\Authenticatable as AuthAuthenticatable;

class User implements Authenticatable, AuthorizableInterface, AuthAuthenticatable, HasApiTokens
{
    use AttributesAware;
    use Authorizable;
    use HasApiToken;
    use TraitsAuthenticatable;

    private function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function tokenExpires()
    {
        if (!is_object($this->accessToken)) {
            return true;
        }
        return $this->accessToken->expires();
    }

    public function tokenExpiresAt()
    {
        return $this->accessToken->expiresAt();
    }
}
