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

use Drewlabs\Contracts\OAuth\Token as OAuthToken;

interface Token extends OAuthToken
{
    /**
     * get the list of scopes granted to this token.
     * 
     * @return array 
     */
    public function getScopes(): array;

    /**
     * checks whether the token has expire.
     *
     * @return bool
     */
    public function expires(): bool;
}