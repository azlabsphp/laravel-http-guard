<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\AuthHttpGuard\Exceptions\AuthenticatableNotFoundException;
use Drewlabs\AuthHttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\AuthHttpGuard\Exceptions\UnAuthorizedException;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Exception;
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
     * 
     * @var ApiTokenAuthenticatableProvider
     */
    private $provider;

    /**
     * Create a new guard instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param ApiTokenAuthenticatableProvider $provider
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
     * @param  Request  $request
     * @return mixed
     */
    public function __invoke($request)
    {
        foreach (Arr::wrap(HttpGuardGlobals::guards()) as $guard) {
            if ($user = $this->auth->guard($guard)->user()) {
                return $this->supportsTokens($user)
                    ? $user->withAccessToken(new TransientToken)
                    : $user;
            }
        }
        if ($token = $request->bearerToken()) {
            try {
                return $this->provider->getByOAuthToken($token);
            } catch (Exception $e) {
                if (
                    $e instanceof AuthenticatableNotFoundException ||
                    $e instanceof TokenExpiresException ||
                    $e instanceof UnAuthorizedException
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
     * @param  mixed  $tokenable
     * @return bool
     */
    protected function supportsTokens($tokenable = null)
    {
        return $tokenable instanceof HasApiTokens || method_exists($tokenable, 'withAccessToken');
    }
}
