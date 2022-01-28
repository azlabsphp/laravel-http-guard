<?php

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
        $this->authorizations =  $value;
    }

    public function setAuthorizationGroups(array $value = [])
    {
        $this->roles = $value;
    }

    public function can($ability, $arguments = [])
    {
        if (in_array('*', $this->accessToken->abilities())) {
            return in_array($ability, $this->getAuthorizations());
        }
        return $this->tokenCan($ability);
    }

    public function cant($ability, $arguments = [])
    {
        return !$this->can($ability, $arguments);
    }
}