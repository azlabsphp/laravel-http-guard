<?php

namespace Drewlabs\AuthHttpGuard\Contracts;

use Drewlabs\Contracts\Auth\Authenticatable;

interface UserFactory
{

    /**
     * Creates the application user instance from the result of the HTTP request.
     * 
     * The $token parameter is the token used to retrieve by the provider to resolve the user attributes.
     * Implementation classes can use it to set the current accessToken of the user
     * 
     * **Note**
     * Implementation classes must throw a {@see \Drewlabs\AuthHttpGuard\Exceptions\ServerBadResponseException}
     * if required attributes are missing from the $attributes parameter.
     * 
     * @param array $attributes 
     * @param null|string $token 
     * @return \Illuminate\Contracts\Auth\Authenticatable|Authenticatable 
     */
    public function create(array $attributes = [], ?string $token = null);

}