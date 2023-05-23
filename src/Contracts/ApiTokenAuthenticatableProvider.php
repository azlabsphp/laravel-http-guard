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

namespace Drewlabs\HttpGuard\Contracts;

use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Exceptions\UnAuthorizedException;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\Curl\REST\Exceptions\RequestException;

interface ApiTokenAuthenticatableProvider
{
    /**
     * Get authenticable instance for user provided token
     * 
     * @param string $token 
     * @return null|Authenticatable 
     * @throws TokenExpiresException 
     * @throws UnAuthorizedException 
     * @throws ServerException 
     */
    public function getByOAuthToken(string $token): ?Authenticatable;

    /**
     * Revoke the connected user auth token.
     * 
     * @param string $token 
     * @return void 
     * @throws UnAuthorizedException 
     * @throws RequestException 
     * @throws ServerException 
     */
    public function revokeOAuthToken(string $token);
}
