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

namespace Drewlabs\AuthHttpGuard\Contracts;

use Drewlabs\Contracts\Auth\Authenticatable;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Drewlabs\AuthHttpGuard\Exceptions\ServerException;
use Drewlabs\AuthHttpGuard\Exceptions\UnAuthorizedException;

interface ApiTokenAuthenticatableProvider
{
    /**
     * Fetch the authenticatable using user provided token.
     * 
     * @param string $token 
     * @return null|Authenticatable 
     */
    public function getByOAuthToken(string $token): ?Authenticatable;

    /**
     * Revoke the connected user auth token
     * 
     * @param string $token 
     * @return void 
     * @throws UnAuthorizedException 
     * @throws BadResponseException 
     * @throws ServerException 
     * @throws GuzzleException 
     */
    public function revokeOAuthToken(string $token);
}
