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

final class JsonProperties
{
    /** @var string */
    private $accessTokenProperty = 'accessToken';

    /** @var string */
    private $usernameProperty = 'username';

    /** @var string */
    private $userIdProperty = 'id';

    /** @var string */
    private $rememberTokenProperty = 'remember_token';

    /** @var string */
    private $passwordProperty = 'password';

    /** @var string */
    private $authorizationsProperty = 'authorizations';

    /** @var string */
    private $groupsProperty = 'roles';


    public function getAccessTokenProperty(): string
    {
        return $this->accessTokenProperty;
    }

    public function setAccessTokenProperty(string $value): self
    {
        $this->accessTokenProperty = $value;
        return $this;
    }

    public function getUsernameProperty(): string
    {
        return $this->usernameProperty;
    }

    public function setUsernameProperty(string $value): self
    {
        $this->usernameProperty = $value;
        return $this;
    }

    public function getUserIdProperty(): string
    {
        return $this->userIdProperty;
    }

    public function setUserIdProperty(string $value): self
    {
        $this->userIdProperty = $value;
        return $this;
    }

    public function getRememberTokenProperty(): string
    {
        return $this->rememberTokenProperty;
    }

    public function setRememberTokenProperty(string $value): self
    {
        $this->rememberTokenProperty = $value;
        return $this;
    }

    public function getPasswordProperty(): string
    {
        return $this->passwordProperty;
    }

    public function setPasswordProperty(string $value): self
    {
        $this->passwordProperty = $value;
        return $this;
    }

    public function getAuthorizationsProperty(): string
    {
        return $this->authorizationsProperty;
    }

    public function setAuthorizationsProperty(string $value): self
    {
        $this->authorizationsProperty = $value;
        return $this;
    }

    public function getGroupsProperty(): string
    {
        return $this->groupsProperty;
    }

    public function setGroupsProperty(string $value): self
    {
        $this->groupsProperty = $value;
        return $this;
    }
}
