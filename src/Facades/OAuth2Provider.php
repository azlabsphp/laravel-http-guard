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

namespace Drewlabs\HttpGuard\Facades;

use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\OAuth2\AuthenticatableProvider;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin ApiTokenAuthenticatableProvider
 */
final class OAuth2Provider extends Facade
{
    public static function getFacadeAccessor()
    {
        return AuthenticatableProvider::class;
    }
}
