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

namespace Drewlabs\AuthHttpGuard\Traits;

/**
 * @property string[] $authorizations
 * @property string[] $roles
 */
trait Authorizable
{
    public function getAuthorizations()
    {
        return $this->authorizations;
    }

    public function getAuthorizationGroups()
    {
        return $this->roles;
    }

    public function setAuthorizations(array $value = [])
    {
        $this->authorizations = $value;
    }

    public function setAuthorizationGroups(array $value = [])
    {
        $this->roles = $value;
    }

    public function can($ability, $arguments = [])
    {
        if (\in_array('*', $this->accessToken->abilities(), true)) {
            return \in_array($ability, $this->getAuthorizations(), true);
        }

        return $this->tokenCan($ability);
    }

    public function cant($ability, $arguments = [])
    {
        return !$this->can($ability, $arguments);
    }
}
