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

namespace Drewlabs\HttpGuard\Contracts;

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
     * Implementation classes must throw a {@see \Drewlabs\HttpGuard\Exceptions\ServerBadResponseException}
     * if required attributes are missing from the $attributes parameter.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|Authenticatable
     */
    public function create(array $attributes = [], ?string $token = null);
}
