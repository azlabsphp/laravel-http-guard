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
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\HttpGuard\Contracts\UserFactory as AbstractUserFactory;
use Drewlabs\Contracts\Auth\Authenticatable;
use Exception;
use InvalidArgumentException;

class UserFactory implements AbstractUserFactory
{
    /** @var class-string<Authenticatable> */
    private $model;

    /** @var JsonProperties */
    private $properties;

    /**
     * user factory class constructor
     * 
     * @param class-string<Authenticatable> $model 
     * @return void 
     * @throws Exception 
     */
    public function __construct($model, JsonProperties $properties)
    {
        $this->model = $model;
        $this->properties = $properties;
    }

    public function create(array $attributes = [], ?string $token = null)
    {
        if (!class_exists($this->model)) {
            throw new InvalidArgumentException(sprintf('%s class does not exists', $this->model));
        }

        $user = new User(
            $this->getPropertyValue($attributes, $this->properties->getUserIdProperty()),
            $this->getPropertyValue($attributes, $this->properties->getUsernameProperty()),
            $this->getPropertyValue($attributes, $this->properties->getPasswordProperty()),
            $this->getPropertyValue($attributes, $this->properties->getAuthorizationsProperty()),
            $this->getPropertyValue($attributes, $this->properties->getGroupsProperty()),
            $attributes
        );

        if ($this->supportsTokens($user)) {
            $accessToken = AccessToken::createFromAttributes($this->getPropertyValue($attributes, $this->properties->getAccessTokenProperty()) ?? []);
            $user->withAccessToken($accessToken->setAccessToken($token));
        }

        return $user;
    }

    /**
     * checks if the tokenable model supports API tokens.
     *
     * @param mixed $tokenable
     *
     * @return bool
     */
    private function supportsTokens($tokenable = null)
    {
        return $tokenable instanceof HasApiTokens || (method_exists($tokenable, 'token') && method_exists($tokenable, 'withAccessToken'));
    }


    /**
     * return the value for the `property` attribute
     * 
     * @param array $attributes 
     * @param string $property 
     * @return mixed 
     */
    private function getPropertyValue(array $attributes, string $property)
    {
        return Arr::get($attributes, $property);
    }
}
