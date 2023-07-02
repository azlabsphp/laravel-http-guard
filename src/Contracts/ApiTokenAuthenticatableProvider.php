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
use Drewlabs\Curl\REST\Exceptions\RequestException;
use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\UnAuthorizedException;

interface ApiTokenAuthenticatableProvider
{
    /**
     * Get authenticable instance for user provided token.
     *
     * @throws TokenExpiresException
     * @throws UnAuthorizedException
     * @throws ServerException
     */
    public function getByOAuthToken(string $token): ?Authenticatable;

    /**
     * Revoke the connected user auth token.
     *
     * @throws UnAuthorizedException
     * @throws RequestException
     * @throws ServerException
     *
     * @return void
     */
    public function revokeOAuthToken(string $token);
}
