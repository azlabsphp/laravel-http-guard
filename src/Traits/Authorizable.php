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

use Illuminate\Contracts\Auth\Access\Gate;

/**
 * @property string[] $authorizations
 * @property string[] $roles
 */
trait Authorizable
{
    public function getAuthorizations(): array
    {
        return $this->authorizations ?? [];
    }

    public function getAuthorizationGroups(): array
    {
        // TODO : Change roles property or attribute name to groups
        return $this->roles ?? [];
    }

    public function setAuthorizations(array $value = [])
    {
        $this->authorizations = $value;
    }

    public function setAuthorizationGroups(array $value = [])
    {
        // TODO : Change roles property or attribute name to groups
        $this->roles = $value;
    }

    /**
     * Determine if the entity has a given ability.
     *
     * @param string      $ability
     * @param array|mixed $arguments
     *
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        return self::createResolver(Gate::class)()->forUser($this)->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param string      $ability
     * @param array|mixed $arguments
     *
     * @return bool
     */
    public function cant($ability, $arguments = [])
    {
        return !$this->can($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param string      $ability
     * @param array|mixed $arguments
     *
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return $this->cant($ability, $arguments);
    }
}
