<?php

namespace Drewlabs\AuthHttpGuard\Contracts;

use Drewlabs\Contracts\Auth\Authenticatable;

interface ApiTokenAuthenticatableProvider
{
    /**
     * Fetch the authenticatable using user provided token
     * 
     * 
     * @param string $token 
     * @return Authenticatable|null 
     */
    public function getByOAuthToken(string $token): ?Authenticatable;

    /**
     * 
     * @param string $token 
     * @return void 
     */
    public function revokeOAuthToken(string $token);

}