<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\RequestGuard;

class ServiceProvider extends SupportServiceProvider
{

    public function register()
    {
        $this->app->bind(ApiTokenAuthenticatableProvider::class, function ($app) {
            $factory = new CacheProviderFactory;
            $config = $app['config'];
            $provider = $config->get('auth.guards.' . HttpGuardGlobals::guard() . '.provider');
            $model = $config->get('providers.' . $provider . '.model');
            HttpGuardGlobals::authenticatableClass($model ?? class_exists(\Drewlabs\OAuthUser\User::class) ? Drewlabs\OAuthUser\User::class : User::class);
            return new AuthenticatableProvider($factory->make(HttpGuardGlobals::defaultCacheDriver()));
        });

        if (class_exists(RequestGuard::class)) {
            Auth::resolved(function ($auth) {
                $auth->extend(HttpGuardGlobals::guard(), function ($app) use ($auth) {
                    return tap($this->createGuardInstance($app, $auth), function ($guard) use ($app) {
                        $app->refresh('request', $guard, 'setRequest');
                    });
                });
            });
        }
    }

    /**
     * Register the guard.
     *
     * @param \Illuminate\Contracts\Auth\Factory  $auth
     * @param \ArrayAccess $name
     * @return RequestGuard
     */
    private function createGuardInstance($app, $auth)
    {
        return new RequestGuard(
            new Guard($auth, $app[ApiTokenAuthenticatableProvider::class]),
            $app('request'),
            null
        );
    }
}
