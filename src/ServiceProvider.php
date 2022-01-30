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

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    public function register()
    {
        $this->app->bind(ApiTokenAuthenticatableProvider::class, static function ($app) {
            $factory = new CacheProviderFactory();
            $config = $app['config'];
            // Load memcached configurations
            HttpGuardGlobals::forMemcached($config['database.stores.memcached']);
            // Load user configuration
            $provider = $config->get('auth.guards.'.HttpGuardGlobals::guard().'.provider');
            $model = $config->get('providers.'.$provider.'.model');
            HttpGuardGlobals::authenticatableClass($model ?? class_exists(\Drewlabs\OAuthUser\User::class) ? Drewlabs\OAuthUser\User::class : User::class);

            return new AuthenticatableProvider($factory->make(HttpGuardGlobals::defaultCacheDriver()));
        });

        if (class_exists(RequestGuard::class)) {
            Auth::resolved(function ($auth) {
                $auth->extend(HttpGuardGlobals::guard(), function ($app) use ($auth) {
                    return tap($this->createGuardInstance($app, $auth), static function ($guard) use ($app) {
                        $app->refresh('request', $guard, 'setRequest');
                    });
                });
            });
        }
    }

    /**
     * Register the guard.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return RequestGuard
     */
    private function createGuardInstance($app, $auth)
    {
        return new RequestGuard(
            new Guard($auth, $app[ApiTokenAuthenticatableProvider::class]),
            $app['request'],
            null
        );
    }
}
