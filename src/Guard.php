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

use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\UnAuthorizedException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

class Guard
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    private $auth;

    /**
     * @var ApiTokenAuthenticatableProvider
     */
    private $provider;

    /**
     * Create a new guard instance.
     *
     * @return self
     */
    public function __construct(AuthFactory $auth, ApiTokenAuthenticatableProvider $provider)
    {
        $this->auth = $auth;
        $this->provider = $provider;
    }

    /**
     * Retrieve the authenticated user for the incoming request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function __invoke($request)
    {
        try {
            foreach (Arr::wrap(HttpGuardGlobals::defaultGuards()) as $guard) {
                if ($user = $this->auth->guard($guard)->user()) {
                    return $this->supportsTokens($user)
                        ? $user->withAccessToken(new TransientToken())
                        : $user;
                }
            }
        } catch (\Throwable $e) {
            // We pass down if the default guard can't authenticate user to the http guard check
        }
        if ($token = $request->bearerToken()) {
            try {
                return $this->provider->getByOAuthToken($token);
            } catch (\Exception $e) {
                if (
                    $e instanceof AuthenticatableNotFoundException
                    || $e instanceof TokenExpiresException
                    || $e instanceof UnAuthorizedException
                    || $e instanceof ServerBadResponseException
                ) {
                    return null;
                }
                throw $e;
            }
        }
    }

    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param mixed $tokenable
     *
     * @return bool
     */
    protected function supportsTokens($tokenable = null)
    {
        return $tokenable instanceof HasApiTokens || method_exists($tokenable, 'withAccessToken');
    }
}
