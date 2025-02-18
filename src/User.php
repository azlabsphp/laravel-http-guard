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
        // After object attributes is filled, we check if required attributes are present
        // on the object
        $this->validateAttributes();
    }

    /**
     * Returns a boolean value indicationg whether the user is verified / Not.
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
        $hasRequiredAttributes = null !== $this->getAuthIdentifier() && $this->isVerified() && null !== $this->getAuthUserName();
        if (!$hasRequiredAttributes) {
            throw new ServerBadResponseException('missing required attributes');
        }
    }
}
