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

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\AuthorizationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Http\Request;

class Guard
{
    /** @var AuthFactory */
    private $auth;

    /** @var ApiTokenAuthenticatableProvider */
    private $provider;

    /** @var string|string[] */
    private $guards;

    /**
     * creates a new guard instance.
     * 
     * 
     * @param string|string[] $guards
     *
     * @return static
     */
    public function __construct(AuthFactory $auth, ApiTokenAuthenticatableProvider $provider, $guards)
    {
        $this->auth = $auth;
        $this->provider = $provider;
        $this->guards = $guards;
    }

    /**
     * retrieve the authenticated user for the incoming request.
     *
     * @param Request $request
     *
     * @return Authenticatable|HasApiTokens|BaseAuthenticatable|null
     */
    public function __invoke($request): Authenticatable|HasApiTokens|BaseAuthenticatable|null
    {
        try {
            foreach (Arr::wrap($this->guards) as $guard) {
                if ($user = $this->auth->guard($guard)->user()) {
                    return $this->supportsTokens($user) ? $this->castAsHasApiTokens($user)->withAccessToken(new TransientToken()) : $user;
                }
            }
        } catch (\Throwable $e) {
        }

        if (!$token = $request->bearerToken()) {
            return null;
        }


        try {
            return $this->provider->getByOAuthToken($token);
        } catch (AuthenticatableNotFoundException | TokenExpiresException | AuthorizationException | ServerBadResponseException $e) {
            return null;
        }
    }

    /**
     * checks if the tokenable model supports API tokens.
     *
     * @param mixed $tokenable
     *
     * @return bool
     */
    protected function supportsTokens($tokenable = null): bool
    {
        return $tokenable instanceof HasApiTokens || method_exists($tokenable, 'withAccessToken');
    }


    /**
     * 
     * @template T
     * 
     * @param T $user
     * 
     * @return HasApiTokens 
     */
    private function castAsHasApiTokens($user)
    {
        return $user;
    }
}
