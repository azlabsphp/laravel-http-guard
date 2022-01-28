<?php

namespace Drewlabs\AuthHttpGuard\Traits;

use Drewlabs\AuthHttpGuard\AccessToken;
use LogicException;

/**
 * @property AccessToken $accessToken
 */
trait HasApiToken
{
    /**
     * Get the current access token being used by the user.
     *
     * @return PersonalAccessToken|Token|null
     */
    public function token()
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param  string  $ability
     * @return bool
     */
    public function tokenCan(string $ability)
    {
        return $this->accessToken && $this->accessToken->can($ability);
    }

    /**
     * Get the access token currently associated with the user.
     *
     * @return AccessToken
     */
    public function currentAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  AccessToken  $accessToken
     * @return self
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        throw new LogicException("Current authenticatable instance cannot create a new token");
    }
}
